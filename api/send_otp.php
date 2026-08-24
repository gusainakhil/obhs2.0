<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");


/*
|--------------------------------------------------------------------------
| OPTIONS REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


/*
|--------------------------------------------------------------------------
| RESPONSE FUNCTION
|--------------------------------------------------------------------------
*/

function sendResponse(
    $status,
    $message,
    $data = null,
    $httpCode = 200
) {
    http_response_code($httpCode);

    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| ONLY POST ALLOWED
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    sendResponse(
        false,
        "Only POST method is allowed!",
        null,
        405
    );
}


/*
|--------------------------------------------------------------------------
| GET INPUT
|--------------------------------------------------------------------------
*/

$input = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($input)) {
    $input = [];
}


/*
|--------------------------------------------------------------------------
| GET MOBILE NUMBER
|--------------------------------------------------------------------------
*/

$mobile = trim(
    $input['mobile']
    ?? $_POST['mobile']
    ?? ''
);


/*
|--------------------------------------------------------------------------
| MOBILE REQUIRED
|--------------------------------------------------------------------------
*/

if ($mobile === '') {

    sendResponse(
        false,
        "Mobile number is required!",
        null,
        400
    );
}


/*
|--------------------------------------------------------------------------
| CLEAN MOBILE NUMBER
|--------------------------------------------------------------------------
*/

$mobile = preg_replace(
    '/[^0-9]/',
    '',
    $mobile
);


/*
|--------------------------------------------------------------------------
| VALIDATE INDIAN MOBILE NUMBER
|--------------------------------------------------------------------------
*/

if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {

    sendResponse(
        false,
        "Please enter a valid 10-digit mobile number!",
        null,
        400
    );
}


/*
|--------------------------------------------------------------------------
| GENERATE 4 DIGIT OTP
|--------------------------------------------------------------------------
*/

try {

    $otp = random_int(1000, 9999);

} catch (Exception $e) {

    sendResponse(
        false,
        "Unable to generate OTP!",
        null,
        500
    );
}


/*
|--------------------------------------------------------------------------
| ADD BEA PREFIX
|--------------------------------------------------------------------------
|
| Example:
|
| OTP = 9895
|
| Final OTP = BEA9895
|
*/

$finalOtp = "BEA" . $otp;


/*
|--------------------------------------------------------------------------
| AUTHKEY CONFIGURATION
|--------------------------------------------------------------------------
*/

$authkey = "ccb3614edd5c79ed";

$countryCode = "91";

$sid = "45130";

$company = "Beatle Analytics";

$time = "5_minutes";


/*
|--------------------------------------------------------------------------
| AUTHKEY PARAMETERS
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| Authkey receives `alp` instead of `otp`.
|
*/

$params = [

    "authkey" => $authkey,

    "mobile" => $mobile,

    "country_code" => $countryCode,

    "sid" => $sid,

    "company" => $company,

    "alp" => $finalOtp,

    "time" => $time

];


/*
|--------------------------------------------------------------------------
| BUILD AUTHKEY URL
|--------------------------------------------------------------------------
*/

$authkeyUrl =
    "https://api.authkey.io/request?"
    . http_build_query($params);


/*
|--------------------------------------------------------------------------
| CURL REQUEST
|--------------------------------------------------------------------------
*/

$curl = curl_init();

curl_setopt_array($curl, [

    CURLOPT_URL => $authkeyUrl,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_ENCODING => "",

    CURLOPT_MAXREDIRS => 10,

    CURLOPT_TIMEOUT => 30,

    CURLOPT_CONNECTTIMEOUT => 10,

    CURLOPT_FOLLOWLOCATION => true,

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_CUSTOMREQUEST => "GET"

]);


/*
|--------------------------------------------------------------------------
| EXECUTE AUTHKEY REQUEST
|--------------------------------------------------------------------------
*/

$apiResponse = curl_exec($curl);


/*
|--------------------------------------------------------------------------
| CURL ERROR
|--------------------------------------------------------------------------
*/

$curlError = curl_error($curl);


/*
|--------------------------------------------------------------------------
| HTTP CODE
|--------------------------------------------------------------------------
*/

$httpCode = curl_getinfo(
    $curl,
    CURLINFO_HTTP_CODE
);

curl_close($curl);


/*
|--------------------------------------------------------------------------
| CURL FAILED
|--------------------------------------------------------------------------
*/

if ($apiResponse === false || $curlError !== '') {

    sendResponse(
        false,
        "Unable to connect to Authkey service!",
        [
            "mobile" => $mobile,

            "otp" => $finalOtp,

            "otp_number" => (string) $otp,

            "otp_sent" => false,

            "curl_error" => $curlError
        ],
        500
    );
}


/*
|--------------------------------------------------------------------------
| DECODE AUTHKEY RESPONSE
|--------------------------------------------------------------------------
*/

$providerResponse = json_decode(
    $apiResponse,
    true
);


/*
|--------------------------------------------------------------------------
| RAW RESPONSE IF NOT JSON
|--------------------------------------------------------------------------
*/

if (!is_array($providerResponse)) {

    $providerResponse = [
        "raw_response" => $apiResponse
    ];
}


/*
|--------------------------------------------------------------------------
| GET PROVIDER MESSAGE
|--------------------------------------------------------------------------
*/

$providerMessage = '';

if (isset($providerResponse['Message'])) {

    $providerMessage =
        $providerResponse['Message'];

} elseif (isset($providerResponse['message'])) {

    $providerMessage =
        $providerResponse['message'];
}


/*
|--------------------------------------------------------------------------
| CHECK AUTHKEY ERROR
|--------------------------------------------------------------------------
*/

if (
    stripos(
        $providerMessage,
        "invalid authkey"
    ) !== false
    ||
    stripos(
        $providerMessage,
        "insufficient balance"
    ) !== false
) {

    sendResponse(
        false,

        $providerMessage !== ''
            ? $providerMessage
            : "Authkey request failed!",

        [

            "mobile" => $mobile,

            "otp" => $finalOtp,

            "otp_number" => (string) $otp,

            "otp_sent" => false,

            "expires_in_minutes" => 5,

            "provider_response" =>
                $providerResponse

        ],

        502
    );
}


/*
|--------------------------------------------------------------------------
| OTHER PROVIDER ERROR
|--------------------------------------------------------------------------
*/

if (
    stripos(
        $providerMessage,
        "error"
    ) !== false
) {

    sendResponse(
        false,

        $providerMessage,

        [

            "mobile" => $mobile,

            "otp" => $finalOtp,

            "otp_number" => (string) $otp,

            "otp_sent" => false,

            "provider_response" =>
                $providerResponse

        ],

        502
    );
}


/*
|--------------------------------------------------------------------------
| HTTP ERROR
|--------------------------------------------------------------------------
*/

if ($httpCode < 200 || $httpCode >= 300) {

    sendResponse(
        false,

        "OTP service returned an error!",

        [

            "mobile" => $mobile,

            "otp" => $finalOtp,

            "otp_number" => (string) $otp,

            "otp_sent" => false,

            "http_code" => $httpCode,

            "provider_response" =>
                $providerResponse

        ],

        502
    );
}


/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

sendResponse(
    true,

    "OTP sent successfully.",

    [

        "mobile" => $mobile,

        // Frontend response remains `otp`
        "otp" => $finalOtp,

        // Numeric OTP
        "otp_number" => (string) $otp,

        "otp_sent" => true,

        "expires_in_minutes" => 5,

        "provider_response" =>
            $providerResponse

    ],

    200
);

?>