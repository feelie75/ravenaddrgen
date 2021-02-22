<?php
// Thanks for all the help from the following pages, and many many more google searches and stackoverflow:
// http://www.righto.com/2014/02/bitcoins-hard-way-using-raw-bitcoin.html
// https://medium.com/coinmonks/how-to-create-a-raw-bitcoin-transaction-step-by-step-239b888e87f2

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Depending on your host or where you are running the code, this path will vary.
ini_set("include_path", '/home/andrew/php:' . ini_get("include_path") );


include('Math/BigInteger.php');
use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;
// Path to wherever your BitcoinECDSA stuff got installed.
require_once("public_html/ravenfaucet/vendor/bitcoin-php/bitcoin-ecdsa/src/BitcoinPHP/BitcoinECDSA/BitcoinECDSA.php");

$amount = $argv[1];
if ($amount == "") { $amount = 1000; }

print "Generating $amount addresses\n";
$loops = 500; // Generate $amount addresses $loops times
for ($i = 1; $i <= $loops; $i++) {
	$data = generateAddresses($amount);
	$file = "temp/ravenaddress/genadd" . $i . time() . ".csv";
	print "Writing $file\n";
	file_put_contents($file, $data);
}


// The actual MEAT of the code.
function generateAddresses($amount) {
    $bitcoinECDSA = new BitcoinECDSA();
	
	$string = "";

    for ($i=0; $i < $amount; $i++) {
        $bitcoinECDSA->generateRandomPrivateKey();
        $privateKey =  $bitcoinECDSA->getPrivateKey();
        $wif =  $bitcoinECDSA->getWif();
        $publicKey = $bitcoinECDSA->getUncompressedPubKey();

        $address = getAddress($publicKey);

        $string .= "$wif,$privateKey,$publicKey,$address\n";
    }

	return($string);
}

function getAddress($publickey) {

        // hash() likes to have the actual string, not a hex version of it.
        $step1=hexStringToByteString($publickey);

        $step2=hexStringToByteString(hash("sha256",$step1));

        $step3=hexstringToByteString(hash('ripemd160',$step2));

        // Secret sauce, Raven wants hex 3C (ascii 60, or '<') at the forefront.
        $step4="<".$step3;

        // DOUBLE-HASH, MAN! OMG, IT'S A DOUBLE HASH!!! OHHH, OOOOHH, IT'S SOO PRETTTTYYYY!!!
        $step5=hexStringToByteString(hash("sha256",$step4));
        $step6=hexStringToByteString(hash("sha256",$step5));

        // Just the first 4 bytes, since this is still the raw string, not a hex-encoded version.
        $checksum=substr($step6,0,4);

        $step8=$step4.$checksum;

        // bchexdec is custom code I wrote in PHP for handling the big ints!
        $bignum = new Math_BigInteger(bchexdec(byteStringToHexString($step8)));

        // The CLIMAX!
        $address = base58_encode($bignum);

        return($address);
}

// A lot of these functions probably aren't even used, I don't recall. I was just testing stuff.




function bchexdec($hex)
{
        // We could have changed the for-loop to go from right-to-left but this
        // seemed easier to reverse the hex and add stuff up left to right.
    $hex = littleendian($hex);

    $total = new Math_BigInteger(0);

    $len = strlen($hex);
    $multiplier = new Math_BigInteger(1);
    $base = new Math_BigInteger(256);

    for ($i = 0; $i < $len; $i+=2) {
                $currentHex = substr($hex,$i,2);
                $newDec = new Math_BigInteger(hexdec($currentHex));
                $newDec = $newDec->multiply($multiplier);
                $total = $total->add($newDec);

                $multiplier = $multiplier->multiply($base);
    }

    return $total;
}


function base58_encode($input) {

    $alphabet = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
    $base_count = strval(strlen($alphabet));
    $encoded = '';

    $bignum = new Math_BigInteger($input);
    $div = new Math_BigInteger();

    while ($bignum >= $base_count)
    {
        $div = bcdiv($bignum, $base_count);
        $mod = bcmod($bignum, $base_count);
        $encoded = substr($alphabet, intval($mod), 1) . $encoded;

        $bignum = $div;
    }

    if ($bignum > 0)
    {
        $encoded = substr($alphabet, $bignum, 1) . $encoded;
    }

    return($encoded);
}


function littleendian($value) {

        $littleEndian = implode('', array_reverse(str_split($value, 2)));
        return($littleEndian);
}

function hexStringToByteString($hexString){
        //print strlen($hexString) . "\n";
        return(hex2bin($hexString));

    $len=strlen($hexString);

    $byteString="";
    for ($i=0;$i<$len;$i=$i+2){
        $charnum=hexdec(substr($hexString,$i,2));
        $byteString.=chr($charnum);
    }

        return $byteString;
}

function byteStringToHexString($byteString) {
        return(bin2hex($byteString));

    $len=strlen($byteString);

    $hexString = "";

    for ($i=0;$i<$len;$i=$i+1){
        $charnum = sprintf('%02X', (ord(substr($byteString,$i,1))));
        $hexString.=$charnum;
    }

    return $hexString;
}





// For testing stuff.
function benchmark() {
    $bitcoinECDSA = new BitcoinECDSA();
	$bitcoinECDSA->generateRandomPrivateKey(); 				// takes  1171ms to generate 200,000 private keys.
    $privateKey =  $bitcoinECDSA->getPrivateKey(); 			// takes     3ms to generate this 200,000 times.
    $wif =  $bitcoinECDSA->getWif(); 						// takes  6300ms to generate 200,000 wifs
    $publicKey = $bitcoinECDSA->getUncompressedPubKey(); 	// takes 390000ms for only 200,000 public keys
    $address = getAddress($publicKey); 						// takes  25000ms for 200,000 addresses
	// 422474ms to generate all of that 200,000 times.
	// 92.0% towards public key
	//  5.9% towards address
	//  1.5% to wifs
	//  0.3% to random private key
	//  0.0007% to getting private key value

	$rustart = getrusage();
	for ($i=0; $i < 200000; $i++) {
        $address = getAddress($publicKey);
	}
	$ruend = getrusage();
	echo "This process used " . rutime($ruend, $rustart, "utime") .
	    " ms for its computations\n";
	echo "It spent " . rutime($ruend, $rustart, "stime") .
	    " ms in system calls\n";

}

function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}



?>
