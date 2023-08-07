<?php

require_once __DIR__."/logger.php";
require_once __DIR__."/utils.php";

/**
 * This class manages the connection to the OpenAI API.
 */
class OpenAI {
    public $api_key;
    public $DEBUG;

    /**
     * Create a new OpenAI instance.
     * 
     * @param string $api_key The OpenAI API key.
     */
    public function __construct($api_key, $DEBUG = False) {
        $this->api_key = $api_key;
        $this->DEBUG = $DEBUG;
    }

    /**
     * Send a request to the OpenAI API to create a chat completion.
     * 
     * @param object|array $data The data to send to the OpenAI API.
     * @return string The response from GPT or an error message (starts with "Error: ").
     */
    public function gpt($data) {
        // Request a chat completion from OpenAI
        // The response has the following format:
        // $server_output = '{
        //     "id": "chatcmpl-123",
        //     "object": "chat.completion",
        //     "created": 1677652288,
        //     "choices": [{
        //         "index": 0,
        //         "message": {
        //         "role": "assistant",
        //         "content": "\n\nHello there, how may I assist you today?"
        //         },
        //         "finish_reason": "stop"
        //     }],
        //     "usage": {
        //         "prompt_tokens": 9,
        //         "completion_tokens": 12,
        //         "total_tokens": 21
        //     }
        // }';

        // curl https://api.openai.com/v1/chat/completions \
        // -H "Content-Type: application/json" \
        // -H "Authorization: Bearer $OPENAI_API_KEY" \
        // -d '{
        //   "model": "gpt-3.5-turbo",
        //   "messages": [{"role": "user", "content": "Hello!"}]
        // }'

        $response = $this->send_request("chat/completions", $data);
        if (isset($response->choices)) {
            return $response->choices[0]->message->content;
        }
        return $response;
    }

    /**
     * Send a request to the OpenAI API to generate an image.
     * 
     * @param string $prompt The prompt to use for the image generation.
     * @return string The URL of the image generated by DALL-E or an error message.
     */
    public function dalle($prompt) {
        // Request a DALL-E image generation from OpenAI
        // The response has the following format:
        // {
        //     "created": 1680875700,
        //     "data": [
        //         {
        //         "url": "https://example.com/image.png",
        //         }
        //     ]
        // }

        // curl https://api.openai.com/v1/images/generations \
        // -H "Content-Type: application/json" \
        // -H "Authorization: Bearer $OPENAI_API_KEY" \
        // -d '{
        //   "prompt": "a white siamese cat",
        //   "n": 1,
        //   "size": "1024x1024"
        // }'
        $data = array(
            "prompt" => $prompt,
            "n" => 1,
            "size" => "1024x1024",
        );
        $response = $this->send_request("images/generations", $data);
        if (isset($response->data)) {
            $image_url = $response->data[0]->url;
            return $image_url;
        }
        return $response;
    }

    /**
     * Send a request to the OpenAI API.
     * 
     * @param string $endpoint The endpoint to send the request to.
     * @param object|array $data The data to send.
     * @return object|string The response object from the API or an error message (starts with "Error: ").
     */
    private function send_request($endpoint, $data) {
        $url = "https://api.openai.com/v1/".$endpoint;
        $headers = array('Authorization: Bearer '.$this->api_key);

        $response = curl($url, $data, $headers);
        if ($this->DEBUG) {
            Log::debug(array(
                "interface" => "openai",
                "endpoint" => $endpoint,
                "data" => $data,
                "response" => $response,
            ));
        }

        // {
        //     "error": {
        //         "message": "0.1 is not of type number - temperature",
        //         "type": "invalid_request_error",
        //         "param": null,
        //         "code": null
        //     }
        // }
        if (isset($response->error)) {
            if (is_string($data)) {
                $data = json_decode($data);
            }
            Log::error(array(
                "interface" => "openai",
                "endpoint" => $endpoint,
                "data" => $data,
                "response" => $response,
            ));
            // Return the error message
            return 'Error: '.$response->error->message;
        }
        return $response;
    }
}