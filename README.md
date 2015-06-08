# 고대생 인증 모듈 (verifymember)
* 포탈(portal.korea.ac.kr) 로그인을 통한 고대생 인증 모듈입니다.

## 원리
1. 사용자에게 포탈 아이디와 비밀번호를 입력 받습니다.
2. 입력 받은 정보로 포탈(portal.korea.ac.kr)에 로그인을 시도합니다.
3. 성공하면, 사용자 정보를 읽어서 고대생 여부를 확인합니다.

## 사용권
* MIT 라이선스를 따릅니다.
* 포탈 아이디와 비밀번호를 저장하는 수정은 반대합니다.

## 설치 위치
* ./modules/verifymember/

## XE Core 의존성
* XE Core 1.8.1 에서 테스트 되었습니다.
