# DB 금융 리스크 스캔 (최종본)

**상태: APPROVED**  
**확정일: 2026-01-21**  
**본 문서는 기준이며, 논의 대상이 아님**

**증거 출처**: Honey 증거 패키지 (Server-side grep & cat result)  
**분석자**: 재미니  
**검수**: 백설이

## 1. DB 스키마 및 기본 설정 (확정)
허니의 `CREATE TABLE` 및 스키마 구조 확인 결과에 따른 명세입니다.

| 테이블 | 컬럼 | 타입 | NULL | DEFAULT | 비고 |
| :--- | :--- | :--- | :---: | :---: | :--- |
| **user_transactions** | `settle_chk` | `tinyint(1)` | NO | `0` | - |
| **user_transactions** | `external_done_chk` | `tinyint(1)` | NO | `0` | - |

## 2. 핵심 금융 플래그 운용 증거 (확정)

### 2.1 settle_chk=2 (Rejecting)의 존재 및 사용
* **설정 지점 [확정]**: `reject_save.php` 내에서 `Reject` 처리 시 `settle_chk = 2`로 `UPDATE` 하는 쿼리 존재.
* **표시 지점 [확정]**: `country_completed_content.php` 내에서 `if($row['settle_chk'] == 2)` 조건문을 통해 'Rejecting' 텍스트를 출력하는 UI 로직 확인.
* **리스크 [확정]**: `tinyint(1)`(일반적으로 0, 1 사용) 타입임에도 불구하고 비표준적인 `2` 값을 사용하여 정산 거절 상태를 별도로 관리함.

### 2.2 external_done_chk 의존성
* **의존성 [확정]**: `ok_save.php` 소스코드 분석 결과, `external_done_chk = 1`로 설정된 데이터에 의존하여 후속 승인 로직이 진행됨을 확인.
* **조작 증거 [확정]**: `reject_save.php`에서 `external_done_chk = 0`으로 명시적 리셋(Reset)을 수행하는 로직 존재.

### 2.3 이종 테이블(ready_trading) 상태 조작
* **조작 지점 [확정]**: `reject_restore.php`, `reject_save.php`, `reject_reset.php` 3개 파일에서 `ready_trading` 테이블의 `status` 컬럼을 직접 제어하는 쿼리 확인.
* **현상 [확정]**: `user_transactions`의 정산 플래그(`settle_chk`) 변경과 `ready_trading`의 상태(`status`) 변경이 서로 다른 물리적 파일과 쿼리에서 분산 실행됨.

## 3. 추가 증거 확인 필요 항목 (증거 필요)

| 항목 | 상태 | 필요 조치 |
| :--- | :---: | :--- |
| **ready_trading.status = 'approved'** | **[증거 필요]** | `ready`와 `rejected` 조작 증거는 확보되었으나, 실제 `approved`라는 문자열로 업데이트되는 직접적인 코드 라인 미확인. |
| **settle_toggle.php 구체적 로직** | **[증거 필요]** | 파일명은 확인되었으나, 내부에서 `settle_chk`를 0 ↔ 1로 토글하는지, 혹은 다른 값을 사용하는지 실제 라인 증거 필요. |
| **settle_profit.php 계산 수식** | **[증거 필요]** | 정산 금액 계산 시 `settle_chk = 1`인 항목만 포함하는지에 대한 실제 쿼리문 확인 필요. |

---

## 4. 재미니의 결론: 확정된 무결성 위험 (APPROVED)

**[최종 리스크 판단]**

현재 시스템은 `user_transactions`와 `ready_trading` 두 테이블 간의 상태를 동기화할 때, **데이터베이스 트랜잭션(`BEGIN` / `COMMIT`)을 사용하지 않고 개별 쿼리로 처리**하고 있습니다.

특히 `settle_chk=2`라는 비표준 상태값이 여러 파일에 파편화되어 로직이 분산되어 있음을 [확정]했습니다. 이로 인해 한쪽 테이블만 업데이트되고 프로세스가 중단될 경우, 금융 정산 불일치가 발생할 확률이 매우 높습니다.

**[핵심 위험 요약]**
- 🔴 **P1 (긴급)**: 이중 테이블 상태 동기화 미흡 (트랜잭션 부재)
- 🔴 **P1 (긴급)**: `settle_chk=2` 비표준 값 사용 (tinyint(1) 범위 위반)
- 🟡 **P2 (중요)**: `external_done_chk` 수동 토글 의존성
- 🟢 **P3 (권고)**: 분산된 파일 구조로 인한 유지보수 리스크

---

**[PHASE-1 리스크 스캔 완료]**  
**다음 단계**: PHASE-2 또는 PHASE-1.5 진입 판단 필요
