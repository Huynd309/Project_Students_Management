<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Đề Thi THPTQG2026</title>
    <link rel="icon" type="image/png" href="nhatdao_watermark2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --primary-color: #007bff; 
            --success-color: #28a745; 
            --text-color: #333; 
            --bg-body: #f0f2f5;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: var(--bg-body); color: var(--text-color); }
        
        /* --- HEADER & NAV --- */
        header { background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
        .navbar { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 12px 20px; }
        .logo { font-size: 1.4rem; font-weight: bold; color: var(--primary-color); text-decoration: none; display: flex; align-items: center; gap: 8px; }
        
        .btn-home { background: var(--primary-color); border: none; border-radius: 50px; padding: 8px 20px; transition: 0.3s; cursor: pointer; }
        .btn-home a { color: white; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .btn-home:hover { background: #0056b3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3); }

        /* --- EXAM GRID --- */
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-header { text-align: center; margin-bottom: 40px; }
        
        .exam-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, 320px); 
            gap: 25px; 
            justify-content: center; 
            align-items: stretch; 
        }

        .exam-card { 
            background: #fff; border-radius: 15px; padding: 30px 20px; text-align: center; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 1px solid #eee; transition: 0.3s;
            display: flex; flex-direction: column; height: 100%; 
        }
        .exam-card:hover { transform: translateY(-8px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
        .exam-card i.main-icon { font-size: 3.5rem; color: #dc3545; margin-bottom: 20px; display: block; }
        
        .exam-card h3 { 
            margin: 0 0 10px 0; font-size: 1.15rem; color: #222; 
            min-height: 3.5rem; display: flex; align-items: center; justify-content: center;
        }
        .exam-card p { color: #777; font-size: 0.85rem; margin-bottom: 20px; }

        .btn-group { display: flex; gap: 10px; justify-content: center; margin-top: auto; }
        .btn-action { flex: 1; padding: 10px 5px; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; color: white; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.8rem; }
        .btn-exam { background: var(--primary-color); }
        .btn-answer { background: var(--success-color); }

        /* --- MODAL OPTIMIZATION --- */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); opacity: 0; transition: 0.3s; }
        .modal.show { display: block; opacity: 1; }
        
        .modal-content { 
            width: 92%; height: 92%; margin: 2% auto; background: #fff; 
            border-radius: 15px; display: flex; flex-direction: column; 
            overflow: hidden; transform: scale(0.9); transition: 0.3s; 
        }
        .modal.show .modal-content { transform: scale(1); }

        /* Header Modal có thêm nút Fullscreen */
        .modal-header { 
            padding: 12px 20px; border-bottom: 1px solid #eee; 
            display: flex; justify-content: space-between; align-items: center; 
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .btn-fullscreen { 
            color: #666; font-size: 0.85rem; text-decoration: none; 
            background: #f0f0f0; padding: 5px 12px; border-radius: 20px; 
            transition: 0.3s; font-weight: 500;
        }
        .btn-fullscreen:hover { background: var(--primary-color); color: white; }
        
        .close-btn { font-size: 28px; cursor: pointer; color: #666; transition: 0.2s; }

        .modal-body { 
            flex: 1; background: #525659; overflow-y: auto; 
            -webkit-overflow-scrolling: touch; /* Hỗ trợ cuộn mượt trên iPhone */
        }
        #pdfFrame { width: 100%; height: 100%; border: none; display: block; background: #fff; }

        @media (max-width: 600px) {
            .modal-content { width: 100%; height: 100%; margin: 0; border-radius: 0; }
            .header-left { flex-direction: column; align-items: flex-start; gap: 5px; }
            .btn-group { flex-direction: column; }
            .exam-grid { grid-template-columns: 1fr; } 
        }
    </style>
</head>
<body>

    <header>
        <div class="navbar">
            <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> TRUNG TÂM NHẤT ĐẠO EDU</a>
            <div class="nav-links"><button class="btn-home"><a href="index.php"><i class="fas fa-home"></i> Trang chủ</a></button></div>
        </div>
    </header>

    <div class="container">
        <div class="page-header"><h2>DANH SÁCH ĐỀ THI THỬ THPT 2026</h2></div>
        <div class="exam-grid">
            <div class="exam-card">
                <i class="fas fa-file-pdf main-icon"></i>
                <h3>Đề KSCL lần 3 Sở Ninh Bình 2026</h3>
                <p>Ngày đăng: 07/05/2026</p>
                <div class="btn-group">
                    <button class="btn-action btn-exam" onclick="openSingleModal('Toán Sở Ninh Bình L3', 'uploads/DeThiThu2026/KSCL_NB_2026.pdf')"><i class="fas fa-eye"></i> Xem đề</button>
                    <button class="btn-action btn-answer" onclick="alert('Đang cập nhật đáp án...')"><i class="fas fa-check-circle"></i> Đáp án</button>
                </div>
            </div>
            <div class="exam-card">
                <i class="fas fa-file-pdf main-icon"></i>
                <h3>Đề KSCL lần 2 THPT Thường Xuân 2 - Thanh Hoá -</h3>
                <p>Ngày đăng: 07/05/2026</p>
                <div class="btn-group">
                    <button class="btn-action btn-exam" onclick="openSingleModal('Toán Thường Xuân 2 L2', 'uploads/DeThiThu2026/KSCL_TX2_TH2026.pdf')"><i class="fas fa-eye"></i> Xem đề</button>
                    <button class="btn-action btn-answer" onclick="openSingleModal('Đáp án Thường Xuân 2 L2', 'uploads/DapAn2026/DA_KSCL_TX2_TH2026.pdf')"><i class="fas fa-check-circle"></i> Đáp án</button>
                </div>
            </div>
        </div>
    </div>

    <div id="pdfModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="header-left">
                    <strong id="modalTitle" style="color:var(--primary-color)"></strong>
                    <a id="fullscreenBtn" href="#" target="_blank" class="btn-fullscreen">
                        <i class="fas fa-external-link-alt"></i> Mở toàn màn hình
                    </a>
                </div>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <iframe id="pdfFrame" src=""></iframe>
            </div>
        </div>
    </div>

    <script>
        function openSingleModal(title, fileUrl) {
            const modal = document.getElementById('pdfModal');
            const frame = document.getElementById('pdfFrame');
            const fullBtn = document.getElementById('fullscreenBtn');
            
            document.getElementById('modalTitle').innerText = title;
            
            // Cập nhật link cho nút "Mở toàn màn hình"
            fullBtn.href = fileUrl;

            // Load file vào iframe
            frame.src = fileUrl + "#toolbar=0&navpanes=0&view=FitH";
            
            modal.style.display = 'block';
            setTimeout(() => { modal.classList.add('show'); }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('pdfModal');
            modal.classList.remove('show');
            setTimeout(() => { 
                modal.style.display = 'none'; 
                document.getElementById('pdfFrame').src = "";
            }, 300);
            document.body.style.overflow = 'auto';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('pdfModal')) { closeModal(); }
        }
    </script>
</body>
</html>