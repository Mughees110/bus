<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MessagingException;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        // Replace this with your actual Firebase credentials JSON data
        $firebaseCredentialsJson = '{
  "type": "service_account",
  "project_id": "automoviles-6a644",
  "private_key_id": "8d8f7bb13301b825d2a36ef63df7b70b856f7495",
  "private_key": "-----BEGIN PRIVATE KEY-----\\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCxkNtkuSHUAWYv\\nYzjXKwRM6hzwBY6DZyJbYiBTHUWASwdhNoLu2bnd4tT7QBAxmbeTewDS2HeNJ+H2\\nDKA0SEIbEu97Yz+elDni6irAJUS7hLXkOWyZtce/bGvUknJ+10PiNnVxN0xUYjxj\\nGo/sQ0ThAmRezDMvVkeTwAIFGkCCMsFWCvagHklq9jQyJboEoIjqk70baucLKmf/\\n5TtXYMN272RN841c6NdDokPa6jv8D4c6tCxuGSPzjU/IFS6x6DgRuL8USwIDmQEt\\nhoVTYL0zXH0KMGRNaOgwXTRbAi1LKQ06CBOP5i0Q6AljZT2v2eOGQFkt8/9oQZsR\\nfLF/8+mNAgMBAAECggEAA+YKjx5VIg7AI9u27dslrnD4dC5Rt/cIen7/zIYIwTLv\\nL/ZOryMBe/VE7a71Jrr5hx0tAjQbAd16462uHx++sHynXJXsBGsRj0wgEVUaz87a\\nNdnV6tUNKZneOeNXl2CEvsopJMnM8lBa2OaGe5k+1qg4XxrMJ+aqI9IUKUZaMrw/\\nAXqCfh/Q5VSTRrOlBO6hUCOMWmfk0fukRYYFv9B38fmgzCHtUkDWtPR/45c70OU2\\nCxF528if15Bczvnt2WuR0mCNRU0x5PF8ZYzgxWMxC22km5zZgs+Du1BJTJzkbq/L\\nfg4PsAQD60P+vOVJlWVBgEsgFR54zt8Q2mbNa/S1oQKBgQDg0x+VK+0AW09J6rlP\\nfYRiOWr8T0lV1Kvum+BDGgwU+t8d21FSmmIVIHTosa5DQMDicGt5NIvcoSWu3Pcm\\ne6hBE+6QU3yrRmVKniODhUmincrcBEO/CD37SfcS7IlwJbRGgrpHVvbaUD7rk8e3\\nIeHZoQftwoZ+UHNG+DbONPfu4QKBgQDKMCDkiTwmNXprFQtL3mxUsZHAhhvN33y5\\nUg6tODTsM0jgcXeNxJ44WhXiefgwZSJEBA6vLPPtEoccq3i9HZQ8rtHKrJ6BS28a\\n0lnrvcI48Ptn5c3YSZiZspQ4IhlAZsD29PDTT3L1ttEOlt8xiiC9T3w524Cc8wAt\\njpfGAphsLQKBgEb/ZQHelUF/lFJrZYnMwXmjWD3FbAtG1eTMJM4L87TMZJkxIUVM\\nq5ywWzsAoV9rm33msoncJi7OVPAbp5DnjALBIJ1DQCN2X5Zoyh5GgTJxUhaY4iv5\\nllk8ymGXgO+BeKSrs9fDhsD6hmQujusuL/xh9fcHbyGElmLbD7Oe1o0BAoGBAKhw\\nrBZ4lhmm19O59m1AYbO1Mx3XG/bJRxkE2aFJgB1/JCmHnfgHY2DC/BRvVGrM9lz/\\nnFQn+Rb6JoGmALJcoBBl+/UDFhHVDDymHa+dqN7TND78Xh7gQTdaZMzW636RnBSh\\nPrhsKfO2WDf1TP/yeQ/91hpFWcJnVzCc6KhXpTAJAoGBAJhejxP+QF78azO9f8Iu\\nI+sy7e5rFslvZKdhVWXx8oAuU6qt69FClTr7cJPu6ijWpevdEHEDqibin42fC/Iy\\nwm1BLzllz8Q9QLqqFZ9nuPnb6Y8p1xXGpPovV8aYPbLe/RUiVqD19hYKw31rGK6t\\njY5QXa9hTkpP50YrgWQb/Q7Y\\n-----END PRIVATE KEY-----\\n",
  "client_email": "firebase-adminsdk-wcgko@automoviles-6a644.iam.gserviceaccount.com",
  "client_id": "105559174068160426490",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-wcgko%40automoviles-6a644.iam.gserviceaccount.com",
  "universe_domain": "googleapis.com"
}';

        // Decode JSON data to an array
        $credentials = json_decode($firebaseCredentialsJson, true);

        if (!$credentials) {
            throw new \Exception('Invalid Firebase credentials JSON');
        }

        $factory = (new Factory)
            ->withServiceAccount($credentials);

        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        try {
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(['title' => $title, 'body' => $body])
                ->withData($data);

            // Send notification and capture the response
            $messageId = $this->messaging->send($message);

            // Return success message or the message ID
            return [
                'status' => 'success',
                'message_id' => $messageId
            ];
        } catch (MessagingException $e) {
            // Handle the exception and capture error details
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
}
