
document.addEventListener('DOMContentLoaded', function() {
    
    const toggleSwitch = document.getElementById('checkbox');
    const docElement = document.documentElement;

    if (toggleSwitch) {
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            docElement.setAttribute('data-theme', 'dark'); 
            toggleSwitch.checked = true;
        }

        toggleSwitch.addEventListener('change', function() {
            if (this.checked) {
                docElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark'); 
            } else {
                docElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light'); 
            }
        });
    } 
    // Countdown Timer Logic
    const countdownWidget = document.querySelector('.countdown-widget');
    
    if (countdownWidget) {
        const khoi = countdownWidget.dataset.khoi; 
        let targetDate;

        if (khoi == "12") {
            targetDate = new Date("June 29, 2026 07:00:00").getTime();
        } else if (khoi == "11") {
            targetDate = new Date("June 29, 2027 07:00:00").getTime();
        } else if (khoi == "10") {
            targetDate = new Date("June 29, 2028 07:00:00").getTime();
        }

        if (targetDate) {
            const timerInterval = setInterval(function() {
                const now = new Date().getTime();
                const distance = targetDate - now;

                const daysEl = document.getElementById('days');

                if (distance < 0) {
                    clearInterval(timerInterval);
                    countdownWidget.innerHTML = "<h2>Đã kết thúc kỳ thi!</h2>";
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));

                if (daysEl) daysEl.innerText = days; 
        }, 1000);
        }
    }
    const lessonFilterKhoi = document.getElementById('filterKhoi');
    const lessonFilterMon = document.getElementById('filterMon');
    const lessonTable = document.getElementById('lessonTable');
    const lessonFilterButton = document.getElementById('filterButton'); 
    
    if (lessonFilterKhoi && lessonFilterMon && lessonTable && lessonFilterButton) {
        function filterLessonTable() {
            const allRows = lessonTable.getElementsByTagName('tr');
            if (!allRows || allRows.length === 0) return;
            const tableRows = Array.from(allRows).slice(1);
            const khoiValue = lessonFilterKhoi.value; 
            const monValue = lessonFilterMon.value;   
            for (const row of tableRows) {
                if (!row.cells || row.cells.length <= 2) continue; 
                const cellText = (row.cells[2].textContent || row.cells[2].innerText).trim();
                const items = cellText.split(',').map(s => s.trim());
                let rowMatch = false; 
                for (const item of items) {
                    const parts = item.split('-');
                    if (parts.length === 2) {
                        const itemKhoi = parts[0]; 
                        const itemMon = parts[1];  
                        const khoiMatch = (khoiValue === "") || (itemKhoi === khoiValue);
                        const monMatch = (monValue === "") || (itemMon === monValue);
                        if (khoiMatch && monMatch) {
                            rowMatch = true; 
                            break; 
                        }
                    }
                }
                if (rowMatch) {
                    row.style.display = "table-row"; 
                } else {
                    row.style.display = "none"; 
                }
            }
        }
        lessonFilterButton.addEventListener('click', filterLessonTable);
    } 

    
    const userFilterKhoi = document.getElementById('filterUserKhoi');
    const userFilterButton = document.getElementById('filterUserButton');
    const userTable = document.getElementById('userTable'); 

    if (userFilterKhoi && userFilterButton && userTable) {
        function filterUserTable() {
             const allRows = userTable.getElementsByTagName('tr');
            if (!allRows || allRows.length === 0) return;
            const tableRows = Array.from(allRows).slice(1);
            const khoiValue = userFilterKhoi.value; 
            for (const row of tableRows) {
                if (!row.cells || row.cells.length <= 1) continue; 
                const cellText = (row.cells[1].textContent || row.cells[1].innerText).trim();
                if (cellText === "") {
                    if (khoiValue === "") {
                         row.style.display = "table-row"; 
                    } else {
                         row.style.display = "none";
                    }
                    continue;
                }
                const items = cellText.split(',').map(s => s.trim());
                let rowMatch = false; 
                for (const item of items) {
                    const itemKhoi = item.split('-')[0];
                    if ((khoiValue === "") || (itemKhoi === khoiValue)) {
                        rowMatch = true; 
                        break; 
                    }
                }
                if (rowMatch) {
                    row.style.display = "table-row"; 
                } else {
                    row.style.display = "none"; 
                }
            }
        }
        userFilterButton.addEventListener('click', filterUserTable);
    } 

}); 