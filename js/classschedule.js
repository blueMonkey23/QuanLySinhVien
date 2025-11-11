document.addEventListener("DOMContentLoaded", function() {
  const select = document.getElementById("daySelect");
  const table = document.getElementById("scheduleTable");

  if (select && table) {
    function updateDay() {
      const value = select.value;
      table.className = "schedule-wrapper show-" + value;
    }

    select.addEventListener("change", updateDay);
    updateDay();
  }
});
//thoi khoa bieu

const daySelect = document.getElementById("daySelect");
daySelect.addEventListener("change", () => {
  const dayIndex = {
    mon: 2, tue: 3, wed: 4, thu: 5, fri: 6, sat: 7, sun: 8
  }[daySelect.value];

  document.querySelectorAll(".schedule-header .cell, .schedule-row .cell").forEach((cell, i) => {
    if (i % 8 === dayIndex - 1 || i % 8 === 0) cell.classList.add("show");
    else cell.classList.remove("show");
  });
});

// Gọi 1 lần khi load trang
daySelect.dispatchEvent(new Event("change"));