<?php
/**
 * Mail_Simple: simply sends a mail with attachments.
 * PHP5 and PHP4-compatible.
 *
 * @version 1.03
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
 *         ...
 *         base64-кодировка производится автоматически
 *         ...
 *         </html>
 *     ')),
 *     array(
 *         "img1" => array(
 *             "file" => "path/to/file.gif",
 *             "mime" => "image/gif",
 *             "id"   => "id_of_this_image",
 *             //"data" => "file data if you have not specified 'file' key"
 *         ),
 *         ...
 *     )
 * );
 */
class Mail_Simple
{
    // int mail($mail_text_with_headers, array $attachments)
    function mail($mail, $attachments=null) 
    {
        // Encode mail headers and body
        $mail = Mail_Simple::mailenc($mail);

        // Split the mail by headers and body.
        list ($headers, $body) = preg_split("/\r?\n\r?\n/s", $mail, 2);
        $headers .= "\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
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
        // Remove \r from headers (because GMail could have conflict with them).
        $headers = str_replace("\r", "", $headers);
        // Send mail.
        $opt = null;
        if (preg_match('/<(.*?)>/s', $retpath, $m)) {
            $opt = "-f " . escapeshellarg($m[1]);
        }
        // To avoid DKIM bugs, remove \r.
        $body = str_replace("\r", "", $body);
        $headers = str_replace("\r", "", trim($headers));
        //var_dump($to, $subject, $body, trim($headers), $opt); die();
        mail($to, $subject, $body, $headers, $opt);
    }


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
            $line = Mail_Simple::mailenc_header($line, $encoding);
            $newhead .= "$line\r\n";
        }
        $body = chunk_split(base64_encode($body));
        return "$newhead\r\n$body";
    }


    function mailenc_header($header, $encoding) 
    {
        if (!$encoding) return $header;
        if (!preg_match('/^(From|To|Subject):\s*(.*)/si', $header, $m)) return $header;
        list ($name, $body) = array($m[1], $m[2]);
        $GLOBALS['Mail_Simple_tmp'] = $encoding;
        $body = preg_replace_callback(
            '/((?:^|>)\s*)([^<>]*?[^\w\s,.][^<>]*?)(\s*(?:<|$))/s',
            array('Mail_Simple', 'mailenc_header_callback'),
            $body
        );
        unset($GLOBALS['Mail_Simple_tmp']);
        return $name . ": " . $body;
    }


    function mailenc_header_callback($p) 
    {
        $encoding = $GLOBALS['Mail_Simple_tmp'];
        return $p[1] . "=?$encoding?B?".base64_encode($p[2])."?=" . $p[3];
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
