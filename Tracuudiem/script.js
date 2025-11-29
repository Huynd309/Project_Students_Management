document.addEventListener('DOMContentLoaded', () => {

    const inputId = document.getElementById('student-id');
    const searchBtn = document.getElementById('search-button');
    
    const resultDiv = document.getElementById('result_scores');

    searchBtn.addEventListener('click', () => {
        const sbd = inputId.value.trim();

        if(sbd === ""){
            resultDiv.innerHTML = '<span style="color: red;"> Vui lòng nhập đúng số báo danh</span>';
            return;
        }
        
        resultDiv.innerHTML = 'Đang tìm kiếm kết quả, vui lòng chờ...';

        fetch(`api.php?sbd=${encodeURIComponent(sbd)}&timestamp=${new Date().getTime()}`) 
        .then(response => {
            if(!response.ok){
                throw new Error('Lỗi mạng, vui lòng thử lại sau.');
            }
            return response.json();
        })
        .then(data => {
            if(data.error){
                resultDiv.innerHTML = `<span style="color: red;"> ${data.error} </span>`;
            } else {
                const htmlResult = 
                    `<p><strong>Kết quả vừa tra cứu: ${sbd}</strong></p>
                     <p><strong>Họ và tên:</strong> ${data.ho_ten}</p>`;
                
                resultDiv.innerHTML = htmlResult; 
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<span style="color: red;"> Lỗi khi truy xuất dữ liệu: ${error.message} </span>`;
        });
    });
});