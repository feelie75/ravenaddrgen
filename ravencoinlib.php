<?php 
// Many parts of this code were extracted from the great library at https://github.com/BitcoinPHP/BitcoinECDSA.php

global $a;
global $b;
global $p;
global $n;
global $G;
global $networkPrefix;
$a = gmp_init('0', 10);
$b = gmp_init('7', 10);
$p = gmp_init('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F', 16);
$n = gmp_init('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16);
$G = [
    'x' => gmp_init('55066263022277343669578718895168534326250603453777594175500187360389116729240'),
    'y' => gmp_init('32670510020758816978083085130507043184471273380659243275938904335757337482424')
];
$networkPrefix = '00';

function generateRandomPrivateKey() {
	$key_size = 256;  //For a 256 bit key, you can use 128 or 512 as well. 
	$key = uuid_gen(($key_size / 8)); //8 bits in a byte

	return($key);
}

function uuid_gen($length) {
	$result = "";
    $fp = @fopen('/dev/urandom','rb');

    if ($fp !== FALSE) {
        $result .= @fread($fp, $length);
        @fclose($fp);
    } else {
        trigger_error('Can not open /dev/urandom.');
    }

    return $result;
}

function privateKeyToPublicKey ($privkey) {
    global $G;

    if(empty($pubKeyPts)) {
        $pubKeyPts = mulPoint($privkey, ['x' => $G['x'], 'y' => $G['y']] );

        $pubKeyPts['x'] = gmp_strval($pubKeyPts['x'], 16);
        $pubKeyPts['y'] = gmp_strval($pubKeyPts['y'], 16);

        while(strlen($pubKeyPts['x']) < 64)
        {
            $pubKeyPts['x'] = '0' . $pubKeyPts['x'];
        }

        while(strlen($pubKeyPts['y']) < 64)
        {
            $pubKeyPts['y'] = '0' . $pubKeyPts['y'];
        }
    }


    $uncompressedPubKey = '04' . $pubKeyPts['x'] . $pubKeyPts['y'];

    return $uncompressedPubKey;
}

function mulPoint($k, Array $pG, $base = null) {
        //in order to calculate k*G
        if($base === 16 || $base === null || is_resource($base))
            $k = gmp_init($k, 16);
        if($base === 10)
            $k = gmp_init($k, 10);
        $kBin = gmp_strval($k, 2);

        $lastPoint = $pG;
        for($i = 1; $i < strlen($kBin); $i++)
        {
            if(substr($kBin, $i, 1) === '1')
            {
                $dPt = doublePoint($lastPoint);
                $lastPoint = addPoints($dPt, $pG);
            }
            else
            {
                $lastPoint = doublePoint($lastPoint);
            }
        }
        if(!validatePoint(gmp_strval($lastPoint['x'], 16), gmp_strval($lastPoint['y'], 16)))
            throw new \Exception('The resulting point is not on the curve.');
        return $lastPoint;
}

function doublePoint(Array $pt) {
    global $a;
    global $p;

    $gcd = gmp_strval(gmp_gcd(gmp_mod(gmp_mul(gmp_init(2, 10), $pt['y']), $p),$p));
    if($gcd !== '1') {
        throw new \Exception('This library doesn\'t yet supports point at infinity. See https://github.com/BitcoinPHP/BitcoinECDSA.php/issues/9');
    }

    // SLOPE = (3 * ptX^2 + a )/( 2*ptY )
    // Equals (3 * ptX^2 + a ) * ( 2*ptY )^-1
    $slope = gmp_mod(gmp_mul(gmp_invert(gmp_mod(gmp_mul(gmp_init(2, 10),$pt['y']),$p),$p),gmp_add(gmp_mul(gmp_init(3, 10),
        gmp_pow($pt['x'], 2)),$a)),$p);
    // nPtX = slope^2 - 2 * ptX
    // Equals slope^2 - ptX - ptX
    $nPt = [];
    $nPt['x'] = gmp_mod(gmp_sub(gmp_sub(gmp_pow($slope, 2),$pt['x']),$pt['x']),$p);
    // nPtY = slope * (ptX - nPtx) - ptY
    $nPt['y'] = gmp_mod(gmp_sub(gmp_mul($slope,gmp_sub($pt['x'],$nPt['x'])),$pt['y']),$p);

    return $nPt;
}

function addPoints(Array $pt1, Array $pt2) {
    global $p;

        if(gmp_cmp($pt1['x'], $pt2['x']) === 0  && gmp_cmp($pt1['y'], $pt2['y']) === 0) //if identical
        {
            return doublePoint($pt1);
        }

        $gcd = gmp_strval(gmp_gcd(gmp_sub($pt1['x'], $pt2['x']), $p));
        if($gcd !== '1')
        {                                                                                                                              throw new \Exception('This library doesn\'t yet supports point at infinity. See https://github.com/BitcoinPHP/BitcoinECDSA.php/issues/9');
        }

        // SLOPE = (pt1Y - pt2Y)/( pt1X - pt2X )
        // Equals (pt1Y - pt2Y) * ( pt1X - pt2X )^-1
        $slope = gmp_mod(gmp_mul(gmp_sub($pt1['y'],$pt2['y']),gmp_invert(gmp_sub($pt1['x'],$pt2['x']),$p)),$p);

        // nPtX = slope^2 - ptX1 - ptX2
        $nPt = [];
        $nPt['x']   = gmp_mod(gmp_sub(gmp_sub(gmp_pow($slope, 2),$pt1['x']),$pt2['x']),$p);

        // nPtX = slope * (ptX1 - nPtX) - ptY1
        $nPt['y']   = gmp_mod(gmp_sub(gmp_mul($slope,gmp_sub($pt1['x'],$nPt['x'])),$pt1['y']),$p);

        return $nPt;
}


function validatePoint($x, $y) {
    global $a;
    global $b;
    global $p;

    $x  = gmp_init($x, 16);
    $y2 = gmp_mod(gmp_add(gmp_add(gmp_powm($x, gmp_init(3, 10), $p),gmp_mul($a, $x)),$b),$p);

    $y = gmp_mod(gmp_pow(gmp_init($y, 16), 2), $p);
                                                                                                                               if(gmp_cmp($y2, $y) === 0) {                                                                                                   return true;
    }
    else {
        return false;
    }
}

// pass in the HEX privateKey (32 hex byte string; 64 characters, 256-bits)
function privateKeyToWif($privkey, $compressed = true) {
        while(strlen($privkey) < 64)
            $privkey = '0' . $privkey;

        $secretKey  =  "80" . $privkey; // "ef" for testnet

        if($compressed) {
            $secretKey .= '01';
        }

        $secretKey .= substr(hash('sha256', hex2bin(hash("sha256",(hex2bin($secretKey))))), 0, 8);

        return base58_encode(bchexdec($secretKey));
}

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

function sendTx($rawTx) {

    $url = 'https://ravencoin.network/api/tx/send';
    $myvars = 'rawtx=' . $rawTx;

    $data = array('rawtx' => "$rawTx");

    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }

    var_dump($result);

    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_POST, 1);
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt( $ch, CURLOPT_HEADER, 0);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec( $ch );

    var_dump($response);
}

function getBalance($address) {

    $jsonurl = "https://api.ravencoin.org/api/addr/" . $address;
    $json = file_get_contents($jsonurl, true);
    $results = json_decode($json);

    return $results->balance;
}

function getutxo($address) {
    $jsonurl = "https://api.ravencoin.org/api/addr/" . $address . "/utxo";
    $json = file_get_contents($jsonurl, true);
    $results = json_decode($json,true);
    return($results[0]);
}

function littleendian($value) {

    $littleEndian = implode('', array_reverse(str_split($value, 2)));
    return($littleEndian);
}

function getAddressFromPublicKey($publickey) {

        // hash() likes to have the actual string, not a hex version of it.
        $step1=hex2bin($publickey);

        $step2=hex2bin(hash("sha256",$step1));

        $step3=hex2bin(hash('ripemd160',$step2));

        // Secret sauce, Raven wants hex 3C (ascii 60, or '<') at the forefront.
        $step4="<".$step3;

        // DOUBLE-HASH, MAN! OMG, IT'S A DOUBLE HASH!!! OHHH, OOOOHH, IT'S SOO PRETTTTYYYY!!!
        $step5=hex2bin(hash("sha256",$step4));
        $step6=hex2bin(hash("sha256",$step5));

        // Just the first 4 bytes, since this is still the raw string, not a hex-encoded version.
        $checksum=substr($step6,0,4);

        $step8=$step4.$checksum;

        // bchexdec is custom code I wrote in PHP for handling the big ints!
        $bignum = new Math_BigInteger(bchexdec(bin2hex($step8)));

        // The CLIMAX!
        $address = base58_encode($bignum);

        return($address);
}



?>
