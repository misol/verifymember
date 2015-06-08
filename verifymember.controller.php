<?php
/* Copyright (C) Kim, MinSoo <misol.kr@gmail.com> */
/**
 * @file	verifymember.controller.php
 * @author	MinSoo Kim (misol.kr@gmail.com)
 * @brief	controller class of the verifymember module
 */
class verifymemberController extends verifymember
{
	// 포탈 로그인을 수행하고 인증 정보를 저장
	public function procVerifymemberPortalLogin()
	{
		if(!Context::get('is_logged')) return new Object();
		$logged_info = Context::get('logged_info');

		$id = trim(Context::get('user_id'));
		settype($id, "string");
		$pw = trim(Context::get('password'));
		settype($pw, "string");

		if(!$id || !$pw || Context::getRequestMethod() == 'GET')
		{
			$this->setRedirectUrl(getNotEncodedUrl(''));
			return new Object(-1, 'null_user_id');
		}

		$vars = array(
			'id' => $id,
			'pw' => $pw,
			'direct_div' => '',
			'pw_pass' => ''
		);
		$content = http_build_query($vars);

		$fp = fsockopen('ssl://portal.korea.ac.kr', 443, $errno, $errstr, 15);
		if(!$fp)
		{
			return new Object(-1, 'Can not connect to KU Portal Login');
		}

		fwrite($fp, "POST /common/Login.kpd HTTP/1.1\r\n");
		fwrite($fp, "Host: portal.korea.ac.kr\r\n");
		fwrite($fp, "Content-Length: " . strlen($content) . "\r\n");
		fwrite($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
		fwrite($fp, "Referer: http://portal.korea.ac.kr/front/Intro.kpd\r\n");
		fwrite($fp, "User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:38.0) Gecko/20100101 Firefox/38.0\r\n");
		fwrite($fp, "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n");
		fwrite($fp, "Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.5,en;q=0.3\r\n");
		fwrite($fp, "DNT: 1\r\n");
		fwrite($fp, "Connection: Close\r\n\r\n");
		fwrite($fp, $content);

		$header_buff = '';
		$body_buff = '';
		while(!feof($fp)) {
			$str = fgets($fp, 1024);
			if(trim($str)=='') $start = true;
			if(!$start) $header_buff .= $str;
			if($start) $body_buff .= $str;
		}
		fclose($fp);
		$body_buff .= $header_buff;

		$header_buff_array = explode("\n",$header_buff);
		$need_cookie = '';
		$cookie_count = 0;
		foreach($header_buff_array as $key => $var)
		{
			if(trim($var) === '') continue;
			if(stripos($var,'Set-Cookie:') === FALSE) continue;

			$need_cookie .= trim(substr($var, strpos($var,'Set-Cookie:')+strlen('Set-Cookie:'), strpos($var,"domain=.korea.ac.kr; path=/")-strlen($var))). ' ';
			$cookie_count++;
		}

		if($cookie_count >= 2) // 세션 쿠키와 SSO 쿠키가 전달 될 경우로 예상
		{
			// http://portal.korea.ac.kr/front/ClassConfirm.kpd
			$fp = fsockopen('portal.korea.ac.kr', 80, $errno, $errstr, 15);
			if(!$fp)
			{
				return new Object(-1, 'Can not connect to KU Member information page.');
			}

			fwrite($fp, "GET /front/ClassConfirm.kpd HTTP/1.1\r\n");
			fwrite($fp, "Host: portal.korea.ac.kr\r\n");
			fwrite($fp, "Referer: http://portal.korea.ac.kr/front/ClassConfirm.kpd\r\n");
			fwrite($fp, "User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:38.0) Gecko/20100101 Firefox/38.0\r\n");
			fwrite($fp, "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n");
			fwrite($fp, "Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.5,en;q=0.3\r\n");
			fwrite($fp, "DNT: 1\r\n");
			fwrite($fp, "Cookie: " . trim($need_cookie) . "\r\n");
			fwrite($fp, "Connection: Close\r\n\r\n");

			$header_buff = '';
			$body_buff = '';
			while(!feof($fp))
			{
				$str = fgets($fp, 1024);
				if(trim($str)=='') $start = true;
				if(!$start) $header_buff .= $str;
				if($start) $body_buff .= trim($str);
			}
			fclose($fp);

			$body_buff = substr($body_buff, strpos($body_buff,'<span class="tit_redbullet">')+strlen('<span class="tit_redbullet">'), strpos($body_buff,"소속정보 누락시 전산개발부(Tel:3290-4777)으로 문의바랍니다")-strlen($body_buff));
			$body_buff = substr($body_buff, strpos($body_buff,'</span><ul class="list"><li><strong>')+strlen('</span><ul class="list"><li><strong>'), strpos($body_buff,")</li>")-strlen($body_buff));

			$status = trim(strip_tags(substr($body_buff, 0, strpos($body_buff,'</strong> ')-strlen($body_buff))));

			$student_info = substr($body_buff, strpos($body_buff,'</strong> (')+strlen('</strong> ('));
			$student_info_array = explode(',',$student_info);

			$student_id = trim(strip_tags($student_info_array[0]));
			$student_name = trim(strip_tags($student_info_array[1]));
			$student_dept = trim(strip_tags($student_info_array[3]));

			$oDB = &DB::getInstance();
			$oDB->begin();

			$args = new stdClass();
			if($logged_info->is_admin === 'Y' && intval(Context::get('target_srl')) > 0)
			{
				$member_srl = intval(Context::get('target_srl'));
			}
			else
			{
				$member_srl = $logged_info->member_srl;
			}
			$args->member_srl = $member_srl;
			$args->student_status = $status;
			$args->student_name = $student_name;
			$args->student_id = $student_id;
			$args->student_department = $student_dept;
			$args->status = 'Valid';

			$oVerifymemberModel = getModel('verifymember');
			$is_ku = $oVerifymemberModel->isKUer($member_srl);
			if($is_ku !== FALSE)
			{
				Context::set('verified', TRUE);
			}
			else
			{
				$is_multi = $oVerifymemberModel->getRecordByStudentId($student_id);
				if($is_multi === FALSE)
				{
					$output = executeQuery('verifymember.insertRecord', $args);
					if(!$output->toBool())
					{
						$oDB->rollback();
						return $output;
					}

					// 인증 완료 후 그룹 부여
					$oModuleModel = getModel('module');
					$config = $oModuleModel->getModuleConfig('verifymember');

					$ku_group = $config->ku_group;

					// If the point group exists
					if(intval($ku_group) > 0)
					{
						// Get the default group
						$oMemberModel = getModel('member');
						$default_group = $oMemberModel->getDefaultGroup();

						// Get the removed group and the newly granted group
						$new_group_args = new stdClass;
						$new_group_args->member_srl = $member_srl;
						$new_group_args->group_srl = $ku_group;
						executeQuery('member.addMemberToGroup', $new_group_args);

						// Reset group after initialization
						if($config->group_reset != 'N')
						{
							$del_group_args = new stdClass;
							$del_group_args->member_srl = $member_srl;
							$del_group_args->group_srl = $default_group->group_srl;
							$del_group_output = executeQuery('verifymember.deleteMemberGroup', $del_group_args);
						}
						// Get the removed group and the newly granted group
						$update_group_args = new stdClass;
						$update_group_args->member_srl = $member_srl;
						executeQuery('verifymember.updateMemberGroupTime', $update_group_args);
					}
					$oDB->commit();

					// 캐시 비우기
					$oCacheHandler = CacheHandler::getInstance('object', null, true);
					if($oCacheHandler->isSupport())
					{
						$object_key = 'member_groups:' . getNumberingPath($member_srl) . $member_srl . '_0';
						$cache_key = $oCacheHandler->getGroupKey('member', $object_key);
						$oCacheHandler->delete($cache_key);
					}

					$oCacheHandler = CacheHandler::getInstance('object');
					if($oCacheHandler->isSupport())
					{
						$object_key = 'member_info:' . getNumberingPath($member_srl) . $member_srl;
						$cache_key = $oCacheHandler->getGroupKey('member', $object_key);
						$oCacheHandler->delete($cache_key);
					}
				}
				else
				{
					$this->setRedirectUrl(Context::get('error_return_url'));
					return new Object(-1, 'verifymember_invalid_multi');
				}
			}
			return $this->setRedirectUrl(Context::get('error_return_url'));
		}
		else
		{
			$body_buff = trim(str_replace(array("\r\n","\n","\t"),array('','',''),substr($body_buff, strpos($body_buff,'fnBoard(\'3\', \'1\', true'), strpos($body_buff,'\'#oneid')-strlen($body_buff))));
			$body_buff = substr($body_buff, strpos($body_buff,'alert(\'')+strlen("alert('"), strpos($body_buff,"');")-strlen($body_buff));
			$body_buff = str_replace('로그인이 제한되오니, \nPW찾기를 이용하여 로그인하시기 바랍니다.','포탈 로그인이 제한됩니다.',$body_buff);
			$body_buff = str_replace('사용하시는 브라우저를 종료한 후 새 브라우저에서 다시 시도해 주십시오.',' ',$body_buff);
			$body_buff = htmlspecialchars(trim(str_replace('\n\n',' ',$body_buff)));

			if($body_buff)
			{
				$this->setRedirectUrl(Context::get('error_return_url'));
				return new Object(-1, $body_buff);
			}
			else
			{
				$this->setRedirectUrl(Context::get('error_return_url'));
				return new Object(-1, 'verifymember_failed');
			}
		}

	}

	// 회원 팝업 메뉴에 인증 정보 메뉴를 추가하는 메소드
	public function triggerBeforeMemberPopupMenu()
	{
		$mid = Context::get('mid');
		$logged_info = Context::get('logged_info');
		$member_srl = Context::get('target_srl');
		$oMemberController = getController('member');

		// 관리자 관리용 링크 출력
		if($logged_info->member_srl && $logged_info->is_admin == 'Y')
		{
			$url = getUrl('','module','verifymember','act','dispVerifymemberAdminDetail','view_member_srl',$member_srl);
			$oMemberController->addMemberPopupMenu($url,'verifymember_manage_member',''/*no icon*/,'popup');
		}
	}

	// 회원 메뉴에 고대생 메뉴를 추가하는 메소드
	public function triggerAddMemberMenu()
	{
		$oVerifymemberModel = getModel('verifymember');
		$config = $oVerifymemberModel->getVerifymemberConfig();

		if($config->user_config_list == 'Y')
		{
			if(!Context::get('is_logged')) return new Object();
			$logged_info = Context::get('logged_info');
			//$target_srl = Context::get('target_srl');

			$oMemberController = getController('member');
			$oMemberController->addMemberMenu('dispVerifymemberSettings', '고대생 인증');
		}

		return new Object();
	}

	// 회원 탈퇴시 정보를 지우는 메소드
	public function triggerAfterDeleteMember($obj)
	{
		/* 설정을 통해서 지우지 않을 수도 있는데 일단은 지움
		$oVerifymemberModel = getModel('verifymember');
		$config = $oVerifymemberModel->getVerifymemberConfig();
		*/

		$member_srl = $obj->member_srl;
		if(!$member_srl) return new Object();

		$args = new stdClass();
		$args->member_srl = $member_srl;
		executeQuery('verifymember.deleteRecordByMemberSrl', $args);

		return new Object();
	}
}
/* End of file verifymember.controller.php */
/* Location: ./modules/verifymember/verifymember.controller.php */