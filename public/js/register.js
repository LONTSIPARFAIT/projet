document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const message = document.getElementById('message');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const data = new URLSearchParams(formData).toString();

        fetch('/?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                message.textContent = data.message;
                setTimeout(() => window.location.href = '/?action=dashboard', 1000);
            } else {
                message.textContent = data.error;
            }
        })
        .catch(error => {
            message.textContent = `Erreur : ${error.message}`;
        });
    });
});