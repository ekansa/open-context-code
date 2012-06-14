<?php

class Restmethods extends Zend_Controller_Request_Http
{
    /**
     * Was the request made by GET?
     *
     * @return boolean
     */
    public function isGet()
    {
        if ('GET' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by PUT?
     *
     * @return boolean
     */
    public function isPut()
    {
        if ('PUT' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by DELETE?
     *
     * @return boolean
     */
    public function isDelete()
    {
        if ('DELETE' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by HEAD?
     *
     * @return boolean
     */
    public function isHead()
    {
        if ('HEAD' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by OPTIONS?
     *
     * @return boolean
     */
    public function isOptions()
    {
        if ('OPTIONS' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Return the raw entity body of the request, if present
    *
     * @return string|false Raw entity body, or false if not present
     */
    public function getEntityBody()
    {
        $body = file_get_contents('php://input');

        if (strlen(trim($body)) > 0) {
            return $body;
        }

        return false;
    }

    /**
      * Return the value of the given HTTP header. Pass the header name as the
      * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
      * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     */
}
