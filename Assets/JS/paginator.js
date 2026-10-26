document.addEventListener("DOMContentLoaded", function () {
    const dataList = document.getElementById("data-list");
    const pageButtons = document.getElementById("pageButtons");
    const prevPageButton = document.getElementById("prevPage");
    const nextPageButton = document.getElementById("nextPage");

    const itemsPerPage = 5;
    const data = [...Array(30).keys()];
    let currentPage = 1;

    function displayData(page) {
        dataList.innerHTML = "";
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageData = data.slice(start, end);

        pageData.forEach(item => {
            const li = document.createElement("li");
            li.textContent = `Elemento ${item + 1}`;
            dataList.appendChild(li);
        });
    }

    function updatePaginator() {
        const totalPages = Math.ceil(data.length / itemsPerPage);
        pageButtons.innerHTML = "";

        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement("button");
            button.textContent = i;
            button.addEventListener("click", () => {
                currentPage = i;
                displayData(currentPage);
                updatePaginator();
            });

            if (i === currentPage) {
                button.classList.add("active");
            }

            pageButtons.appendChild(button);
        }

        prevPageButton.disabled = currentPage === 1;
        nextPageButton.disabled = currentPage === totalPages;
    }

    prevPageButton.addEventListener("click", () => {
        if (currentPage > 1) {
            currentPage--;
            displayData(currentPage);
            updatePaginator();
        }
    });

    nextPageButton.addEventListener("click", () => {
        const totalPages = Math.ceil(data.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            displayData(currentPage);
            updatePaginator();
        }
    });

    displayData(currentPage);
    updatePaginator();
});
