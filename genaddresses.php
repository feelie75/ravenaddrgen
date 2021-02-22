<?php
// Thanks for all the help from the following pages, and many many more google searches and stackoverflow:
// http://www.righto.com/2014/02/bitcoins-hard-way-using-raw-bitcoin.html
// https://medium.com/coinmonks/how-to-create-a-raw-bitcoin-transaction-step-by-step-239b888e87f2

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Depending on your host or where you are running the code, this path will vary.
ini_set("include_path", '/home/andrew/php:' . ini_get("include_path") );

// http://pear.php.net/package/Math_BigInteger
include('Math/BigInteger.php');

require_once("./ravencoinlib.php");

$loops = $argv[1];
$amount = $argv[2];
if ($loops == "") { $loops = 500; }
if ($amount == "") { $amount = 1000; }

print "Generating $amount addresses\n";
for ($i = 1; $i <= $loops; $i++) {
	$data = generateAddresses($amount);
	$file = "temp/ravenaddress/genadd" . $i . time() . ".csv";
	print "Writing $file\n";
	file_put_contents($file, $data);
}


// The actual MEAT of the code.
function generateAddresses($amount) {
	$string = "";

    for ($i=0; $i < $amount; $i++) {
		$privateKey = bin2hex(generateRandomPrivateKey());
        $wif =  privateKeyToWif($privateKey);
        $publicKey = privateKeyToPublicKey($privateKey);

        $address = getAddressFromPublicKey($publicKey);

        $string .= "$wif,$privateKey,$publicKey,$address\n";
    }

	return($string);
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
