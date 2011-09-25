<?php
/**
 * Mail_Simple: simply sends a mail with attachments. 
 * PHP5 and PHP4-compatible (yes, the code is in PHP4-style,
 * because it must be PHP4-compatible for some old projects).
 *
 * @version 1.12
 *
 * Usage sample:
 *
 * Mail_Simple::mail(
 *     trim(preg_replace('/^[ \t]+/m', '', '
 *         From: Иван Петров <aa@example.com>
 *         To: Сидор Незаэнкоженный Тоже Допустим <bb@example.com>
 *         Return-Path: <aa@example.com>
 *         Subject: Тоже можно прямо так писать, не кодируя - он автоматом закодируется
 *         Content-Type: text/html; charset=UTF-8
 *
 *         <html>
 *         ...
 *         <img src="cid:id_of_this_image">
 *         <img src="http://example.com/some/image.gif">
 *         ...
 *         quoted-printable-кодировка производится автоматически
 *         ...
 *         </html>
 *     ')), 
 *     array("img1" => array(
 *         "file" => "path/to/file.gif",
 *         "mime" => "image/gif",
 *         "id"   => "id_of_this_image",
 *         //"data" => "file data if you have not specified 'file' key"
 *     ),
 *     true
 * );
 */
class Mail_Simple
{
    /**
     * Sends an email message with attachments (if presented).
     * If $attachExternals is true, all external images in HTML mails
     * are downloaded and attached to the mail body itself.
     *
     * @param string $mail_text_with_headers  Headers are separated from body with newline.
     * @param array $attachments              See attachments format above.
     * @param bool $attachExternals           If true, download and attach extednal images.
     */
    function mail($mail, $attachments=null, $attachExternals=false)
    {
        if ($attachExternals) {
            list($mail, $attached) = Mail_Simple::_attach_externals($mail);
            if (!$attachments) $attachments = array();
            $attachments += $attached;
        }
        // Encode mail headers and body
        $mail = Mail_Simple::mailenc($mail);

        // Split the mail by headers and body.
        list ($headers, $body) = preg_split("/\r?\n\r?\n/s", $mail, 2);
        $headers .= "\r\n";
        $overallHeaders = $headers;

        // Select "To".
        $to = "";
        if (preg_match('/\A(.*)^To:\s*([^\r\n]*)[\r\n]*(.*)\Z/mis', $overallHeaders, $p)) {
            $to = $p[2];
            $overallHeaders = $p[1] . $p[3];
        }
        // Select "Return-Path".
        $retpath = "";
        if (preg_match('/\A(.*)^Return-Path:\s*([^\r\n]*)[\r\n]*(.*)\Z/mis', $overallHeaders, $p)) {
            $retpath = $p[2];
            $overallHeaders = $p[1] . $p[3];
        }
        // Select "Subject".
        $subject = "";
        if (preg_match('/\A(.*)^Subject:\s*([^\r\n]*)[\r\n]*(.*)\Z/mis', $overallHeaders, $p)) {
            $subject = $p[2];
            $overallHeaders = $p[1] . $p[3];
        }

        // Attachment processing.
        if ($attachments) {
            $multiparts = array();
            foreach ($attachments AS $name=>$attachment) {
                $file = null;
                $mime = 'application/octet-stream';
                $id = null;
                $data = null;
                if (is_array($attachment)) {
                    $file = isset($attachment['file'])? $attachment['file'] : null;
                    if (isset($attachment['mime'])) $mime = $attachment['mime'];
                    if (isset($attachment['id'])) $id = $attachment['id'];
                    if (isset($attachment['data'])) $data = $attachment['data'];
                } else {
                    $file = $attachment;
                }

                if ($file !== null && !file_exists($file)) continue;
                if ($data === null) $data = file_get_contents($file);
                if (is_int($name)) $name = $file !== null? basename($file) : $id;
                $type = $id === null? 'mixed' : 'related';
                $head = '';
                $head .= "Content-Type: $mime\r\n";
                $head .= "Content-Disposition: attachment; filename=" . addslashes($name) . "\r\n";
                $head .= "Content-Transfer-Encoding: base64\r\n";
                if ($id !== null) $head .= "Content-ID: <$id>\r\n";
                $head .= "\r\n" . chunk_split(base64_encode($data));
                $multiparts[$type][] = $head;
            }

            // Related multiparts must always be situated on most depth.
            if (isset($multiparts['related'])) {
                $related = $multiparts['related'];
                unset($multiparts['related']);
                $multiparts = array('related'=>$related) + $multiparts;
            }

            foreach ($multiparts as $type=>$parts) {
                array_unshift($parts, $headers . "\r\n" . $body);
                $boundary = md5(uniqid(mt_rand(), true));
                $body = 
                    "--" . $boundary . "\r\n" .
                    join("\r\n--" . $boundary . "\r\n", $parts) . "\r\n" .
                    "--" . $boundary . "--\r\n";
                $headers = "Content-Type: multipart/$type; boundary=$boundary\r\n";
            }

            $overallHeaders = preg_replace('/^Content-Type:\s*.*?\r?\n/mix', '', $overallHeaders);            
            $headers = $overallHeaders . $headers;
        } else {
            $headers = $overallHeaders;
        }
        // Remove \r (because GMail or DKIM could have conflict with them).
        $headers = str_replace("\r", "", trim($headers));
        $body = str_replace("\r", "", $body);
        // Send mail.
        $opt = null;
        if (preg_match('/<(.*?)>/s', $retpath, $m)) {
            $opt = "-f " . escapeshellarg($m[1]);
        }
        //var_dump($to, $subject, $body, trim($headers), $opt); die();
        mail($to, $subject, $body, $headers, $opt);
    }

    /**
     * Encodes all non-encoded headers in the mail.
     * Also encodes mail body.
     *
     * @param string $mail        Mail with headers and body.
     * @param string $encoding    Target encoding (if not presented, autodetected).
     * @return string
     */
    function mailenc($mail, $encoding=null) 
    {
        @list ($head, $body) = preg_split("/\r?\n\r?\n/s", $mail, 2);
        if (!$encoding) {
            $encoding = '';
            $re = '/^Content-type: \s* \S+ \s*; \s* charset \s* = \s* (\S+)/mix';
            if (preg_match($re, $head, $p)) $encoding = $p[1];
        }
        $newhead = "";
        foreach (preg_split('/\r?\n/s', $head) as $line) {
            $line = Mail_Simple::_mailenc_header($line, $encoding);
            $newhead .= "$line\r\n";
        }
        // We ALWAYS use quoted-printable for mail body (not base64), because
        // seems Kaspersky KIS 2012 has a bug while processing base64-encoded
        // bodies when mail headers exceed a particular length (or it is a
        // ESET NOD32 Antivirus server module bug? who knows...).
        if (true) {
            $body = Mail_Simple::encodeQuotedPrintable($body);
            $newhead .= "Content-Transfer-Encoding: quoted-printable\r\n";
        } else {
            $body = chunk_split(base64_encode($body));
            $newhead .= "Content-Transfer-Encoding: base64\r\n";
        }
        return "$newhead\r\n$body";
    }

    /**
     * Performs quoted-printable encoding (this code is from Zend_Mime).
     *
     * @param string $str      String to be encoded.
     * @param int $lineLength  Line length to wrap.
     * @param string $lineEnd  Line ending.
     * @return string
     */
    function encodeQuotedPrintable($str, $lineLength = 72, $lineEnd = "\n")
    {
        $out = '';
        $str = Mail_Simple::_encodeQuotedPrintable($str);

        // Split encoded text into separate lines
        while ($str) {
            $ptr = strlen($str);
            if ($ptr > $lineLength) {
                $ptr = $lineLength;
            }

            // Ensure we are not splitting across an encoded character
            $pos = strrpos(substr($str, 0, $ptr), '=');
            if ($pos !== false && $pos >= $ptr - 2) {
                $ptr = $pos;
            }

            // Check if there is a space at the end of the line and rewind
            if ($ptr > 0 && $str[$ptr - 1] == ' ') {
                --$ptr;
            }

            // Add string and continue
            $out .= substr($str, 0, $ptr) . '=' . $lineEnd;
            $str = substr($str, $ptr);
        }

        $out = rtrim($out, $lineEnd);
        $out = rtrim($out, '=');
        return $out;
    }

    function _attach_externals($text)
    {
        $GLOBALS['Mail_Simple_tmp_attached'] = array();
        $text = preg_replace_callback(
            '/<img[^>]+?src=[\'"](.*?)[\'"][^>]+?>/',
            array('Mail_Simple', '_attach_externals_callback'),
            $text
        );
        $attached = array_values($GLOBALS['Mail_Simple_tmp_attached']);
        unset($GLOBALS['Mail_Simple_tmp_attached']);
        return array($text, $attached);
    }

    function _attach_externals_callback($matches)
    {
        list ($tag, $url) = $matches;
        $id = md5($url);
        if (!isset($GLOBALS['Mail_Simple_tmp_attached'][$id])) {
            $ch = curl_init();
            $options = array(
                CURLOPT_URL            => htmlspecialchars_decode($url),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5
            );
            curl_setopt_array($ch, $options);
            $data = curl_exec($ch);
            if (!$data) return $tag;
            $mime = trim(curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
            if (!preg_match('{^image/}si', $mime)) return $tag;
            $GLOBALS['Mail_Simple_tmp_attached'][$id] = array(
                'data' => $data,
                'mime' => $mime,
                'id'   => $id
            );
        }
        return str_replace($url, 'cid:' . $id, $tag);
    }
    
    function _mailenc_header($header, $encoding) 
    {
        if (!$encoding) return $header;
        if (!preg_match('/^(From|To|Subject):\s*(.*)/si', $header, $m)) return $header;
        list ($name, $body) = array($m[1], $m[2]);
        $GLOBALS['Mail_Simple_tmp'] = $encoding;
        $body = preg_replace_callback(
            '/((?:^|>)\s*)([^<>]*?[^\w\s,.][^<>]*?)(\s*(?:<|$))/s',
            array('Mail_Simple', '_mailenc_header_callback'),
            $body
        );
        unset($GLOBALS['Mail_Simple_tmp']);
        return $name . ": " . $body;
    }

    function _mailenc_header_callback($p) 
    {
        $encoding = $GLOBALS['Mail_Simple_tmp'];
        return $p[1] . "=?$encoding?B?".base64_encode($p[2])."?=" . $p[3];
    }

    function _encodeQuotedPrintable($str)
    {
        static $qpKeys = array(
            "\x00","\x01","\x02","\x03","\x04","\x05","\x06","\x07",
            "\x08","\x09","\x0A","\x0B","\x0C","\x0D","\x0E","\x0F",
            "\x10","\x11","\x12","\x13","\x14","\x15","\x16","\x17",
            "\x18","\x19","\x1A","\x1B","\x1C","\x1D","\x1E","\x1F",
            "\x7F","\x80","\x81","\x82","\x83","\x84","\x85","\x86",
            "\x87","\x88","\x89","\x8A","\x8B","\x8C","\x8D","\x8E",
            "\x8F","\x90","\x91","\x92","\x93","\x94","\x95","\x96",
            "\x97","\x98","\x99","\x9A","\x9B","\x9C","\x9D","\x9E",
            "\x9F","\xA0","\xA1","\xA2","\xA3","\xA4","\xA5","\xA6",
            "\xA7","\xA8","\xA9","\xAA","\xAB","\xAC","\xAD","\xAE",
            "\xAF","\xB0","\xB1","\xB2","\xB3","\xB4","\xB5","\xB6",
            "\xB7","\xB8","\xB9","\xBA","\xBB","\xBC","\xBD","\xBE",
            "\xBF","\xC0","\xC1","\xC2","\xC3","\xC4","\xC5","\xC6",
            "\xC7","\xC8","\xC9","\xCA","\xCB","\xCC","\xCD","\xCE",
            "\xCF","\xD0","\xD1","\xD2","\xD3","\xD4","\xD5","\xD6",
            "\xD7","\xD8","\xD9","\xDA","\xDB","\xDC","\xDD","\xDE",
            "\xDF","\xE0","\xE1","\xE2","\xE3","\xE4","\xE5","\xE6",
            "\xE7","\xE8","\xE9","\xEA","\xEB","\xEC","\xED","\xEE",
            "\xEF","\xF0","\xF1","\xF2","\xF3","\xF4","\xF5","\xF6",
            "\xF7","\xF8","\xF9","\xFA","\xFB","\xFC","\xFD","\xFE",
            "\xFF"
        );
        static $qpReplaceValues = array(
            "=00","=01","=02","=03","=04","=05","=06","=07",
            "=08","=09","=0A","=0B","=0C","=0D","=0E","=0F",
            "=10","=11","=12","=13","=14","=15","=16","=17",
            "=18","=19","=1A","=1B","=1C","=1D","=1E","=1F",
            "=7F","=80","=81","=82","=83","=84","=85","=86",
            "=87","=88","=89","=8A","=8B","=8C","=8D","=8E",
            "=8F","=90","=91","=92","=93","=94","=95","=96",
            "=97","=98","=99","=9A","=9B","=9C","=9D","=9E",
            "=9F","=A0","=A1","=A2","=A3","=A4","=A5","=A6",
            "=A7","=A8","=A9","=AA","=AB","=AC","=AD","=AE",
            "=AF","=B0","=B1","=B2","=B3","=B4","=B5","=B6",
            "=B7","=B8","=B9","=BA","=BB","=BC","=BD","=BE",
            "=BF","=C0","=C1","=C2","=C3","=C4","=C5","=C6",
            "=C7","=C8","=C9","=CA","=CB","=CC","=CD","=CE",
            "=CF","=D0","=D1","=D2","=D3","=D4","=D5","=D6",
            "=D7","=D8","=D9","=DA","=DB","=DC","=DD","=DE",
            "=DF","=E0","=E1","=E2","=E3","=E4","=E5","=E6",
            "=E7","=E8","=E9","=EA","=EB","=EC","=ED","=EE",
            "=EF","=F0","=F1","=F2","=F3","=F4","=F5","=F6",
            "=F7","=F8","=F9","=FA","=FB","=FC","=FD","=FE",
            "=FF"
        );
        $str = str_replace('=', '=3D', $str);
        $str = str_replace($qpKeys, $qpReplaceValues, $str);
        $str = rtrim($str);
        return $str;
    }
}


/*
Multiparts may nest. E.g.:

multipart/mixed
>>multipart/related
>>>>multipart/alternative
>>>>>>text/plain
>>>>>>text/html
>>>>image/jpg
>>>>image/jpg
>>>>image/jpg
>>>>image/jpg
>>application/octet-stream
>>application/octet-stream
>>application/octet-stream
>>application/octet-stream

or:

multipart/mixed
>>multipart/related
>>>>text/plain
>>>>image/jpg
>>>>image/jpg
>>>>image/jpg
>>>>image/jpg
>>application/octet-stream
>>application/octet-stream
>>application/octet-stream
>>application/octet-stream
*/
