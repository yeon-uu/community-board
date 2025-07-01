  구조설명

  프로젝트 구조 설명

  메인 및 홈 관련
- home.php : 로그인 전 메인 페이지
- homehome.php : 로그인 후 메인 페이지
- index.html : 기본 인덱스 페이지
- header.php, footer.html : 공통 헤더/푸터

  사용자 인증
- login.php : 로그인 페이지
- logout.php : 로그아웃 처리
- register.php : 회원가입 페이지

  자유게시판 (board)
- list.php : 게시글 목록
- write.php : 글 작성
- view.php : 글 상세 보기
- edit.php : 글 수정
- delete_post.php : 글 삭제

  댓글 기능
- comment.php : 댓글 출력+입력폼표시
- comment_submit.php : 댓글 등록 처리(DB저장)
- edit_cmt.php : 댓글 수정
- delete_cmt.php : 댓글 삭제

  정보게시판 (info_*)
- info_list.php : 게시글 목록
- info_view.php : 게시글 보기
- info_write.php : 글 작성
- info_edit.php : 글 수정
- info_delete.php : 글 삭제
- info_comment.php : 댓글 출력+저
- info_edit_cmt.php : 댓글 수정
- info_delete_cmt.php : 댓글 삭제

  기타 파일/폴더
- db.php : DB 연결 파일
- uploads/ : 파일 업로드 저장 폴더
- images/ : 이미지 리소스 폴더
- html/ : 기타 html 리소스 (무시됨)
- ubuntu@13.125.221.240, yeonu-key.pem : 테스트 또는 제외 파일 (.gitignore 처리됨)
