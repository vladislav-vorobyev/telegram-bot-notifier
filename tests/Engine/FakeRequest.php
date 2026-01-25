<?php
namespace TNotifyer\Engine;

/**
 * 
 * Fake request class for tests
 * 
 */
class FakeRequest extends Request {

    /**
     * 
     * Constructor.
     * 
     */
    public function __construct($method = 'GET', $uri = '/', $body = '', $headers = [])
    {
        // request method
        $this->request_method = $method;

        // request uri and query
        $this->uri = $uri;
        $parsed_url = parse_url($this->uri);
        if (isset($parsed_url['query']))
            parse_str($parsed_url['query'], $this->params);
        $this->path = $parsed_url['path'];

        // site root uri
        $this->root_uri = $this->root_path;

        // request headers
        $this->headers = $headers;
        
        $this->post = $body;
    }
}