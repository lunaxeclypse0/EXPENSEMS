document.addEventListener('DOMContentLoaded', function () {
    const logoutBtn = document.getElementById('logoutBtn');

    logoutBtn.addEventListener('click', function (e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('action', 'logout');

        fetch('../controllers/AuthController.php', {
            method: 'POST',
            body: formData
        })
            .then((res) => res.json())
            .then((data) => {
                window.location.href = data.redirect || 'login.php';
            })
            .catch(() => {
                window.location.href = 'login.php';
            });
    });
});


