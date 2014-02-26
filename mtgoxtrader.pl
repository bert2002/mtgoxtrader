#!/usr/bin/perl
# script: trade BTC on MtGox
# author: Steffen Wirth <s.wirth@itbert.de>
# donate: BLABLA

use strict;
use warnings;
 
use Time::HiRes qw(gettimeofday);
use MIME::Base64;
use Digest::SHA qw(hmac_sha512);
use DateTime;
use Date::Parse;
use JSON;
use LWP::UserAgent;
use Data::Dumper;
use DBI;

###
# Config
###

my $lwp = LWP::UserAgent->new;
$lwp->agent("perl $]");
my $json = JSON->new->allow_nonref;
my $request;
my $res;

# MtGox 
my $SECRET = 'SECRET_KEY';
my $KEY = 'KEY';
my $FEE = "0.6"; # transaction fee for buying bitcoins in percent

# user config
my $DEBUG = "0"; # enable for stdout logging
my $CURRENCY = "EUR"; # trading currency EUR,USD...
my $SELLMARGIN = "1"; # margin for selling bitcoins in percent
my $BUYMARGIN = "1"; # margin for buying bitcoins in percent
my $LOGPATH = "/path/to/trader/log"; # path to log directory
my $SQLPATH = "/path/to/trader/mtgoxtrader.db"; # Path to sqlite3 database
my $MAILRECIPIENT = "mail\@example.org"; # add email for trade notification on buy/sell
my $MAXLASTBUY = "86400"; # force a buy after x seconds of the last sell
my $MAXLASTSELL = "21600"; # force a sell after x seconds of the last buy, BUT only if a win is calculated (price + fee)

###
# Helper
###

sub genReq {
	my ($uri) = shift;
 
  my $req = HTTP::Request->new(POST => 'https://mtgox.com/api/'.$uri);
  $req->content_type('application/x-www-form-urlencoded');
  $req->content("nonce=".microtime());
  $req->header('Rest-Key' => $KEY);
  $req->header('Rest-Sign' => signReq($req->content(),$SECRET));
 
  return $req;
};

sub genReqTrade {
  my ($uri,$type,$amount,$price) = @_;

  my $nonce = microtime();
	my $req = HTTP::Request->new(POST => 'https://mtgox.com/api/'.$uri);
  $req->content_type('application/x-www-form-urlencoded');
  $req->content("nonce=$nonce&type=$type&amount_int=$amount");
  $req->header('Rest-Key' => $KEY);
  $req->header('Rest-Sign' => signReq($req->content(),$SECRET));

  return $req;
};
 
sub signReq {
  my ($content,$secret) = @_;
  return encode_base64(hmac_sha512($content,decode_base64($secret)));
}
 
sub microtime { return sprintf "%d%06d", gettimeofday; }

# logging
mkdir "$LOGPATH/", 0777 unless -d "$LOGPATH";
open (LOGPATH, ">>$LOGPATH/mtgoxtrader.log") or die "can not open logfile $LOGPATH/mtgoxtrader.log";
sub mylog {
  my ($message) = @_;
  my $date = localtime;
  if ($DEBUG) {
   print         "$date  [$$] $message\n";
  } else {
   print LOGPATH "$date  [$$] $message\n";
  }
}

# send email
sub myemail {

	my ($action, $amount, $price, $message) = @_; 
	if ($MAILRECIPIENT) {
		system("/bin/echo \"$action $amount bitcoins for $price. $message\" | mailx -s \"[TRADE] $action\" $MAILRECIPIENT");
	}

}

# sqlite
my $dbargs = {AutoCommit => 0, PrintError => 1};
my $dbh = DBI->connect("dbi:SQLite:dbname=$SQLPATH", "", "", $dbargs);

###
# Script
###

# get ticker information
$request = genReq("1/BTC$CURRENCY/ticker");
$res = $lwp->request($request);

if ($res->is_success) {
	my $content = $json->decode($res->content);
	my $sell = $content->{'return'}{'sell'}{'value_int'};
	my $buy = $content->{'return'}{'buy'}{'value_int'};
	mylog("[STATUS] currency: $CURRENCY buy: $buy sell: $sell");
	
	# insert data to db
	$dbh->do("INSERT INTO data (buy, sell) VALUES ($buy,$sell);");
	$dbh->commit();

	## BUY BITCOINS
	$res = $dbh->selectall_arrayref("SELECT id,amount,price,timestamp FROM sold WHERE status=0;");
  foreach my $row (@$res) {
  	my ($id, $amount, $price, $timestamp) = @$row;
		mylog("[BUY] +++");
	  mylog("[+++] last sold amount: $amount price $price");

		# calculate time difference from last sell
		my $now = time();	
		my $dbdate = str2time($timestamp);
		my $difference = $now - $dbdate; 
		mylog("[+++] last sell was $difference seconds ago");

    # add margin
    if ($BUYMARGIN) {
      my $price_margin = ($price * $BUYMARGIN) / 100;
      $price = $price - $price_margin;
    }
    $price =~ s/\..*//; #remove after dot
    mylog("[+++] price - margin $price");

		if ( ($price > $sell) || ($difference > $MAXLASTBUY) ) {
			mylog("[+++] buy $amount bitcoins");
			# buy bitcoins
			$request = genReqTrade("1/BTC$CURRENCY/private/order/add","bid",$amount,$sell);
			$res = $lwp->request($request);
			if ($res->is_success) {
				my $content = $json->decode($res->content);
				my $oid = $content->{'return'};
				my $result = $content->{'result'};
				mylog("[+++] status oid: $oid result: $result");
				$dbh->do("UPDATE sold SET status=1 WHERE id=$id");
				$dbh->do("INSERT INTO bought (amount,price,status,oid) VALUES ($amount,$sell,0,\"$oid\")");
				$dbh->commit;
				myemail("bought","$amount","$sell","");
			} else {
				my $status = $res->status_line;
				mylog("error: $status");
			}
		} else {
			mylog("[+++] to expensive");
		}
	}

	# SELL BITCOINS
  $res = $dbh->selectall_arrayref("SELECT id,amount,price,timestamp FROM bought WHERE status=0;");
  foreach my $row (@$res) {
		my ($id, $amount, $price, $timestamp) = @$row;
		mylog("[SELL] ---");
		mylog("[---] last bought amount: $amount price $price");

    # calculate time difference from last sell
    my $now = time();
    my $dbdate = str2time($timestamp);
    my $difference = $now - $dbdate;
    mylog("[+++] last buy was $difference seconds ago");

    # add exchange fee
    if ($FEE) {
      my $price_fee = ($price * $FEE) / 100;
      $price = $price + $price_fee;
    }
    my $price_org = $price;

		# add margin
		if ($SELLMARGIN) {
			my $price_margin = ($price * $SELLMARGIN) / 100;
			$price = $price + $price_margin;
		}
		$price =~ s/\..*//; #remove after dot
		$price_org =~ s/\..*//; #remove after dot
		mylog("[---] price + margin and fee: $price");					

		if ( ($buy > $price) || ($difference > $MAXLASTSELL) && ($buy > $price_org) ){
			mylog("[SELL] $amount bitcoins");
			# sell bitcoins
      $request = genReqTrade("1/BTC$CURRENCY/private/order/add","ask",$amount,$buy);
      $res = $lwp->request($request);
      if ($res->is_success) {
				my $content = $json->decode($res->content);
        my $oid = $content->{'return'};
        my $result = $content->{'result'};
        mylog("[+++] status oid: $oid result: $result");
				$dbh->do("UPDATE bought SET status=1 WHERE id=$id");
				$dbh->do("INSERT INTO sold (amount,price,status,oid) VALUES ($amount,$buy,0,\"$oid\")");
				$dbh->commit;
				myemail("sold","$amount","$buy","");
     	} else {
         my $status = $res->status_line;
         mylog("error: $status");
      }

		} else {
			mylog("[---] not worth selling");
		}
	}

} else { 
	my $status = $res->status_line;
	mylog("error: $status"); 
}

$dbh->disconnect; 



