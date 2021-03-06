<?php


namespace MiladZamir\Orange;


use Illuminate\Support\Facades\Http;

class Orange
{
    /**
     * @param array $receptor
     * @param string $message
     * @param int|null $sender
     * @param int|null $date
     * @param string|null $type
     * @param string|null $localid
     * @param bool|null $hide
     * @param bool $debug
     * @return array|false|\Illuminate\Http\JsonResponse|mixed
     */
    public static function smsSend(array $receptor, string $message, int $sender = null, int $date = null, string $type = null, string $localid = null, bool $hide = null, bool $debug = false)
    {
        $receptor = implode(',', $receptor);
        $message = urlencode($message);
        $BasicUrl = self::getBaseApiUrl() . self::getApiToken() . self::getMethodApiUrl('sms', 'send');

        $client = Http::post($BasicUrl .
            "receptor=" . $receptor .
            "&message=" . $message .
            "&sender=" . $sender .
            "&date" . $date .
            "&type" . $type .
            "&localid" . $localid .
            "&hide" . $hide
        );

        $result = $client->json();

        if ($debug == true)
            return $client->json();
        elseif ($result['return']['status'] == 200) {
            try {
                self::storeSmsLog($result);
            }catch (\Exception $e){
                return false;
            }
            return response()->json(['status' => 200]);
        }

        try {
            self::storeSmsLog($result , true);
        }catch (\Exception $e){
            return false;
        }

        return response()->json(['status' => 422]);
    }

    /**
     * @param string $receptor
     * @param string $token
     * @param string|null $token2
     * @param string|null $token3
     * @param string $template
     * @param string|null $type
     * @param bool $debug
     * @return array|false|\Illuminate\Http\JsonResponse|mixed
     */
    public static function smsLookup(string $receptor, string $token, string $token2 = null, string $token3 = null, string $template, string $type = null, bool $debug = false)
    {
        $BasicUrl = self::getBaseApiUrl() . self::getApiToken() . self::getMethodApiUrl('verify', 'lookup');
        $client = Http::post($BasicUrl .
            "receptor=" . $receptor .
            "&token=" . $token .
            "&token2=" . $token2 .
            "&token3=" . $token3 .
            "&template=" . $template .
            "&type" . $type
        );
        $result = $client->json();

        if ($debug == true)
            return $client->json();
        elseif ($client->json()['return']['status'] == 200) {
            try {
                self::storeSmsLog($result);
            }catch (\Exception $e){
                return false;
            }
            return response()->json(['status' => 200]);
        }

        try {
            self::storeSmsLog($result , true);
        }catch (\Exception $e){
            return false;
        }

        return response()->json(['status' => 422]);
    }

    /**
     * @param string $linenumber
     * @param bool $isread
     * @param bool $debug
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    public static function smsReceive(string $linenumber, bool $isread, bool $debug = false)
    {
        if ($isread == true)
            $isread = 1;
        else
            $isread = 0;

        $BasicUrl = self::getBaseApiUrl() . self::getApiToken() . self::getMethodApiUrl('sms', 'receive');


        $client = Http::post($BasicUrl .
            "linenumber=" . $linenumber .
            "&isread=" . $isread
        );

        if ($debug == true) {
            return $client->json();
        } elseif ($client->json()['return']['status'] == 200) {
            return response()->json(['entries' => $client->json()['entries']]);
        }

        return response()->json(['status' => 422]);

    }

    /**
     * @return string
     */
    private static function getBaseApiUrl()
    {
        return 'https://api.kavenegar.com/v1/';
    }

    /**
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private static function getApiToken()
    {
        return config('orange.api_key');
    }

    /**
     * @param $method
     * @param $type
     * @return string
     */
    private static function getMethodApiUrl($method, $type)
    {
        return '/' . $method . '/' . $type . '.json?';
    }

    /**
     * @param $result
     * @param bool $failed
     * @return bool
     */
    private static function storeSmsLog($result, bool $failed = false)
    {
        if ($failed != false){
            \MiladZamir\Orange\Models\Orange::create([
                'status' => $result['return']['status'],
                'result_message' => $result['return']['message'],
            ]);
            return true;
        }
        foreach ($result['entries'] as $value){
            \MiladZamir\Orange\Models\Orange::create([
                'status' => $result['return']['status'],
                'result_message' => $result['return']['message'],
                'message_id' => $value['messageid'],
                'message' => $value['message'],
                'status_entries' => $value['status'],
                'status_text' => $value['statustext'],
                'sender' => $value['sender'],
                'receptor' => $value['receptor'],
                'date' => $value['date'],
                'cost' => $value['cost'],
            ]);
        }

        return true;
    }

}
