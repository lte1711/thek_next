# TheK-NEXT 투자/에이전트 관리 시스템

다단계 투자 및 에이전트 관리를 위한 PHP 기반 웹 애플리케이션

## 📋 시스템 요구사항

- PHP 8.1 이상
- MySQL 8.0 이상
- Apache 2.4 이상 (mod_rewrite 활성화)
- PHP Extensions:
  - mysqli
  - mbstring
  - json
  - session

## 🚀 설치 방법

### 1. 파일 설정

```bash
# config.example.php를 복사하여 config.php 생성
cp config.example.php config.php

# config.php 편집 (데이터베이스 정보 입력)
nano config.php
```

### 2. 데이터베이스 설정

```sql
-- 데이터베이스 생성
CREATE DATABASE thek_next_db_branch_jp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 사용자 생성 및 권한 부여
CREATE USER 'thek_db_admin'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON thek_next_db_branch_jp.* TO 'thek_db_admin'@'localhost';
FLUSH PRIVILEGES;

-- 테이블 생성 (SQL 파일 임포트)
mysql -u thek_db_admin -p thek_next_db_branch_jp < thek_next_db_branch_jp.sql
```

### 3. 권한 설정

```bash
# 로그 디렉토리 생성
mkdir -p logs
chmod 755 logs

# 파일 소유권 설정 (Apache 사용자로)
chown -R www-data:www-data .
chmod -R 755 .
```

### 4. Apache 설정

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/thek-next
    
    <Directory /var/www/html/thek-next>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/thek-error.log
    CustomLog ${APACHE_LOG_DIR}/thek-access.log combined
</VirtualHost>
```

## 🔒 보안 설정

### 필수 보안 조치

1. **config.php 보호**
   - config.php의 비밀번호를 반드시 변경하세요
   - 파일 권한을 600으로 설정하세요: `chmod 600 config.php`

2. **SECRET_KEY 변경**
   - config.php의 SECRET_KEY를 고유한 값으로 변경하세요
   ```php
   define('SECRET_KEY', 'your-unique-secret-key-here');
   ```

3. **HTTPS 사용**
   - 프로덕션 환경에서는 반드시 HTTPS를 사용하세요
   - config.php에서 `SECURE_COOKIE`를 true로 설정하세요

4. **디버그 모드 비활성화**
   - 프로덕션 환경에서는 `DEBUG_MODE`를 false로 설정하세요

### 추가 보안 권장사항

- 정기적으로 비밀번호 변경
- 로그 파일 정기적으로 확인
- PHP 및 MySQL 최신 버전 유지
- 방화벽 설정
- 백업 정기 수행

## 🌐 다국어 지원

지원 언어:
- 한국어 (ko) - 기본
- 일본어 (ja)
- 영어 (en)

언어 전환: 우측 상단 언어 선택기 사용

## 👥 사용자 역할

1. **Super Admin** - 최고 관리자
2. **GM (Global Master)** - 글로벌 마스터
3. **Admin** - 관리자
4. **Master** - 마스터
5. **Agent** - 에이전트
6. **Investor** - 투자자

## 📁 디렉토리 구조

```
.
├── includes/           # 공통 PHP 파일
│   ├── i18n.php       # 다국어 시스템
│   ├── session.php    # 세션 관리
│   └── security.php   # 보안 유틸리티
├── lang/              # 언어 파일
│   ├── ko.php         # 한국어
│   ├── ja.php         # 일본어
│   └── en.php         # 영어
├── css/               # 스타일시트
├── logs/              # 로그 파일
├── config.php         # 설정 파일 (생성 필요)
└── *.php              # 페이지 파일
```

## 🔧 주요 기능

### 계정 관리
- 사용자 등록/수정/삭제
- 역할 기반 권한 관리
- 추천인 시스템

### 거래 관리
- 입금/출금 처리
- 거래 내역 조회
- 국가별 거래 관리 (한국, 일본, 캄보디아)

### 정산 시스템
- 파트너 정산
- 조직 정산
- 배당 계산 및 분배

### 보고서
- GM 리포트
- 관리자 대시보드
- 통계 및 차트

## 🛠️ 유지보수

### 로그 확인

```bash
# 에러 로그 확인
tail -f logs/error.log

# Apache 로그 확인
tail -f /var/log/apache2/thek-error.log
```

### 백업

```bash
# 데이터베이스 백업
mysqldump -u thek_db_admin -p thek_next_db_branch_jp > backup_$(date +%Y%m%d).sql

# 파일 백업
tar -czf backup_files_$(date +%Y%m%d).tar.gz .
```

### 업데이트

```bash
# Git을 사용하는 경우
git pull origin main

# 수동 업데이트
# 1. 백업 수행
# 2. 새 파일로 교체
# 3. config.php는 유지
# 4. 데이터베이스 마이그레이션 실행 (필요시)
```

## 📞 지원

문제가 발생하면:
1. logs/error.log 확인
2. Apache 에러 로그 확인
3. PHP 버전 및 확장 모듈 확인
4. 데이터베이스 연결 확인

## 📄 라이선스

이 프로젝트는 TheK-NEXT의 소유입니다.

## 🔄 변경 이력

### v21 (2026-01-16)
- 런타임 에러 수정 (current_lang null 반환)
- 다국어 번역 완성 (한국어, 일본어)
- 줄바꿈 문자 통일 (CRLF → LF)
- 설정 파일 시스템 추가
- 보안 유틸리티 추가
- 세션 관리 개선

### 이전 버전
- 다국어 지원 구현
- 보안 강화 (Prepared Statements)
- UI/UX 개선
