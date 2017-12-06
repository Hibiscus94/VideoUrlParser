<?php
/**
 * Video Parser
 * 
 * Damien Wang 
 * update time : 2017/12/6
 * 
 */
 /*
  * 测试代码留存
 
 //$video = new Services_VideoUrlParser();
 //$url[] = 'https://v.qq.com/x/cover/jb6wejrvi609u6f.html';
 //$url[] = 'http://v.youku.com/v_show/id_XMzIwNzUyMjgyOA==.html';
 //$url[] = 'http://www.iqiyi.com/v_19rre7t640.html';
 foreach($url as $key => $val)
 {
	 var_dump($video->parse($val));
 }
 
 */
class Services_VideoUrlParser
{
	const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/601.2.7 (KHTML, like Gecko) Version/9.0.1 Safari/601.2.7';
	const CHECK_URL_VALID = "/(v\.qq\.com|youku\.com|iqiyi\.com)/";

	/**
	 * parse
	 *
	 * @param string $url
	 * @static
	 * @access public
	 * @return void
	 */
	static public function parse($url = '')
	{
		$lowerurl = strtolower($url);

		if (strstr($lowerurl, '.swf'))
		{
			return '<p><object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0" width="640" height="400"><param name="movie" value="' . $url . '" /><param name="quality" value="high" /><param name="wmode" value="transparent" /><param name="allowFullScreen" value="true" /><embed src="' . $url . '" quality="high" pluginspage="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" width="640" height="400" wmode="transparent" allowfullscreen="true"></embed></object></p>';
		}

		preg_match(self::CHECK_URL_VALID, $lowerurl, $matches);

		if (!$matches)
		{
			return '<p>[<a href="'.$lowerurl.'" target="_blank">查看视频</a>]</p>';
		}

    switch ($matches[1])
		{
			case 'v.qq.com' :
				$data = self::_parseQq($url);
				break;
			case 'youku.com' :
				$data = self::_parseYouku($url);
				break;
			case 'iqiyi.com' :
				$data = self::_parseIqiyi($url);
				break;
			default :
				return $url;
		}

		if ($data)
		{
			if (isset($data['iframe']))
			{
				return '<p><iframe width="640" height="400" src="' . $data['iframe'] . '" frameborder="0" allowfullscreen="allowfullscreen"></iframe></p>';
			}
			else
			{
				return '<p><object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0" width="640" height="400"><param name="movie" value="' . $data['swf'] . '" /><param name="quality" value="high" /><param name="wmode" value="transparent" /><param name="allowFullScreen" value="true" /><embed src="' . $data['swf'] . '" quality="high" pluginspage="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" width="640" height="400" wmode="transparent" allowfullscreen="true"></embed></object></p>';
			}
		}

		return '<p>[<a href="'.$lowerurl.'" target="_blank">视频解析失败</a>]</p>';
	}

	/**
	 * 腾讯视频
	 * 
	 */
	static private function _parseQq($url)
	{
		$html = self::_cget($url);

		preg_match('/vid":"(\w+)"/i', $html, $matches);

		if (!$vid = $matches[1])
		{
			return false;
		}

		/*preg_match('/vname_title":"(\.*)"/i', $html, $matches);
		$data['title'] = $matches[1];*/

		preg_match('/pic_640_360":"(.+)"/i', $html, $matches);
		$data['img'] = $matches[1];

		$data['url'] = $url;
		$data['iframe'] = 'https://v.qq.com/iframe/player.html?vid=' . $vid . '&auto=0';
		//$data['swf'] = 'http://static.video.qq.com/TPout.swf?vid=' . $vid . '&auto=0';

		return $data;
	}

	/**
	 * 优酷网
	 * http://v.youku.com/v_show/id_XMzIwMjg4Njk4MA==.html
	 */
	static private function _parseYouku($url)
	{
		preg_match("#id\_(\w+(?:==)?)#", $url, $matches);

		if (!$matches)
		{
			return false;
		}

		return array(
			'url' => $url,
			'iframe' => "http://player.youku.com/embed/{$matches[1]}"
		);

	}
  
	/**
	 * 爱奇艺
	 */
	static private function _parseIqiyi($url)
	{
		$html = self::_cget($url);

		preg_match('/player-videoid="(\w+)"/i', $html, $matches);

		if (!$videoid = $matches[1])
		{
			return false;
		}
		preg_match("#v\_(\w+(?:==)?)#", $url, $matches);
		$vid = $matches[1];
		preg_match('/player-albumid="(\w+)"/i', $html, $matches);
		$albumid = $matches[1];
		preg_match('/player-tvid="(\w+)"/i', $html, $matches);
		$tvid = $matches[1];
		
		return array(
			'url' => $url,
			'swf' => 'http://player.video.qiyi.com/'. $videoid .'/0/0/v_'. $vid .'.swf-albumId='. $albumid .'-tvId='. $tvid
		);
		
		var_dump($videoid);
	}

	/**
	 * 56网
	 * http://www.56.com/u73/v_NTkzMDcwNDY.html
	 * http://player.56.com/v_NTkzMDcwNDY.swf
	 http://tv.sohu.com/upload/static/share/share_play.html#93267570_245572917_0_9001_0
	 */
	static private function _parse56($url)
	{
		$html = self::_cget($url);

		preg_match('/vid: "(\w+)"/i', $html, $matches);

		if (!$vid = $matches[1])
		{
			return false;
		}
		preg_match('/pid: "(\w+)"/i', $html, $matches);
		
		if (!$pid = $matches[1])
		{
			return false;
		}
		
		$data['swf'] = 'http://share.vrs.sohu.com/my/v.swf&topBar=1&id='. $vid .'&autoplay=false&from=page';
		
		return $data;
		
	}

	// 搜狐TV http://my.tv.sohu.com/u/vw/5101536
	static private function _parseSohu($url)
	{
		$html = iconv('GBK', 'UTF-8', self::_fget($url));

		preg_match_all('#<meta property="og:(title|image|videosrc)" content="(.+)" />#i', $html, $matches);

		$data['img'] = $matches[2][2];
		$data['title'] = $matches[2][1];
		$data['url'] = $url;
		$data['swf'] = $matches[2][0];

		return $data;
	}

	/*
     * 通过 file_get_contents 获取内容
     */
	static private function _fget($url = '')
	{
		if (!$url)
		{
			return false;
		}

		$html = self::_vita_get_url_content($url);
		// 判断是否gzip压缩
		if ($dehtml = self::_gzdecode($html))
		{
			return $dehtml;
		}
		else
		{
			return $html;
		}
	}

	/*
     * 通过 fsockopen 获取内容
     */
	static private function _fsget($path = '/', $host = '', $user_agent = '')
	{
		if (!$path || !$host)
		{
			return false;
		}

		$user_agent = $user_agent ? $user_agent : self::USER_AGENT;

		$out = <<<HEADER
GET $path HTTP/1.1
Host: $host
User-Agent: $user_agent
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
Accept-Language: zh-cn,zh;q=0.5
Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7\r\n\r\n
HEADER;
		$fp = @fsockopen($host, 80, $errno, $errstr, 10);
		if (!$fp)
			return false;
		if (!fputs($fp, $out))
			return false;
		while (!feof($fp))
		{
			$html .= fgets($fp, 1024);
		}
		fclose($fp);
		// 判断是否gzip压缩
		if ($dehtml = self::_gzdecode($html))
			return $dehtml;
		else
			return $html;
	}

	/*
     * 通过 curl 获取内容
     */
	static private function _cget($url = '', $user_agent = '')
	{
		if (!$url)
		{
			return;
		}

		$user_agent = $user_agent ? $user_agent : self::USER_AGENT;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		if (strlen($user_agent))
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

		ob_start();
		curl_exec($ch);
		$html = ob_get_contents();
		ob_end_clean();

		if (curl_errno($ch))
		{
			curl_close($ch);
			return false;
		}

		curl_close($ch);

		if (!is_string($html) || !strlen($html))
		{
			return false;
		}

		return $html;
		// 判断是否gzip压缩
		if ($dehtml = self::_gzdecode($html))
			return $dehtml;
		else
			return $html;
	}

	static private function _vita_get_url_content($url)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

		$file_contents = curl_exec($ch);

		curl_close($ch);

		return $file_contents;
	}

	static private function _gzdecode($data)
	{
		$len = strlen($data);

		if ($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b"))
		{
			return null; // Not GZIP format (See RFC 1952)
		}

		$method = ord(substr($data, 2, 1)); // Compression method
		$flags = ord(substr($data, 3, 1)); // Flags

		if ($flags & 31 != $flags)
		{
			// Reserved bits are set -- NOT ALLOWED by RFC 1952
			return null;
		}

		// NOTE: $mtime may be negative (PHP integer limitations)
		$mtime = unpack("V", substr($data, 4, 4));
		$mtime = $mtime[1];
		$xfl = substr($data, 8, 1);
		$os = substr($data, 8, 1);
		$headerlen = 10;
		$extralen = 0;
		$extra = "";

		if ($flags & 4)
		{
			// 2-byte length prefixed EXTRA data in header
			if ($len - $headerlen - 2 < 8)
			{
				return false; // Invalid format
			}
			$extralen = unpack("v", substr($data, 8, 2));
			$extralen = $extralen[1];
			if ($len - $headerlen - 2 - $extralen < 8)
			{
				return false; // Invalid format
			}
			$extra = substr($data, 10, $extralen);
			$headerlen += 2 + $extralen;
		}

		$filenamelen = 0;
		$filename = "";

		if ($flags & 8)
		{
			// C-style string file NAME data in header
			if ($len - $headerlen - 1 < 8)
			{
				return false; // Invalid format
			}
			$filenamelen = strpos(substr($data, 8 + $extralen), chr(0));
			if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8)
			{
				return false; // Invalid format
			}
			$filename = substr($data, $headerlen, $filenamelen);
			$headerlen += $filenamelen + 1;
		}

		$commentlen = 0;
		$comment = "";

		if ($flags & 16)
		{
			// C-style string COMMENT data in header
			if ($len - $headerlen - 1 < 8)
			{
				return false; // Invalid format
			}
			$commentlen = strpos(substr($data, 8 + $extralen + $filenamelen), chr(0));
			if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8)
			{
				return false; // Invalid header format
			}
			$comment = substr($data, $headerlen, $commentlen);
			$headerlen += $commentlen + 1;
		}

		$headercrc = "";

		if ($flags & 1)
		{
			// 2-bytes (lowest order) of CRC32 on header present
			if ($len - $headerlen - 2 < 8)
			{
				return false; // Invalid format
			}

			$calccrc = crc32(substr($data, 0, $headerlen)) & 0xffff;
			$headercrc = unpack("v", substr($data, $headerlen, 2));
			$headercrc = $headercrc[1];
			if ($headercrc != $calccrc)
			{
				return false; // Bad header CRC
			}

			$headerlen += 2;
		}

		// GZIP FOOTER - These be negative due to PHP's limitations
		$datacrc = unpack("V", substr($data, -8, 4));
		$datacrc = $datacrc[1];
		$isize = unpack("V", substr($data, -4));
		$isize = $isize[1];

		// Perform the decompression:
		$bodylen = $len - $headerlen - 8;

		if ($bodylen < 1)
		{
			// This should never happen - IMPLEMENTATION BUG!
			return null;
		}
		$body = substr($data, $headerlen, $bodylen);
		$data = "";
		if ($bodylen > 0)
		{
			switch ($method)
			{
				case 8 :
					// Currently the only supported compression method:
					$data = gzinflate($body);
					break;
				default :
					// Unknown compression method
					return false;
			}
		}
		else
		{
			//...
		}

		if ($isize != strlen($data) || crc32($data) != $datacrc)
		{
			// Bad format!  Length or CRC doesn't match!
			return false;
		}
		return $data;
	}
}
