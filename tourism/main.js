function toggleMobileMenu() {
    const navLinks = document.getElementById('navLinks');
    const authButtons = document.getElementById('authButtons');

    navLinks.classList.toggle('mobile-active');
    authButtons.classList.toggle('mobile-active');
}

window.addEventListener('resize', function () {
    if (window.innerWidth > 768) {
        const navLinks = document.getElementById('navLinks');
        const authButtons = document.getElementById('authButtons');
        navLinks.classList.remove('mobile-active');
        authButtons.classList.remove('mobile-active');
    }
});

document.addEventListener('click', function (event) {
    const navbar = document.querySelector('.nav-container');
    const mobileMenu = document.querySelector('.mobile-menu');
    const navLinks = document.getElementById('navLinks');
    const authButtons = document.getElementById('authButtons');

    if (!navbar.contains(event.target)) {
        navLinks.classList.remove('mobile-active');
        authButtons.classList.remove('mobile-active');
    }
});

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

function handleContactSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const alertDiv = document.getElementById('contactAlert');
    const submitBtn = form.querySelector('.btn-submit');

    const formData = {
        name: form.name.value,
        email: form.email.value,
        phone: form.phone.value,
        subject: form.subject.value,
        message: form.message.value
    };

    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';

    fetch('submit_contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertDiv.className = 'show success';
                alertDiv.textContent = '✓ ' + data.message;
                form.reset();
            } else {
                alertDiv.className = 'show error';
                alertDiv.textContent = '⚠️ ' + data.message;
            }
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Message';

            setTimeout(() => {
                alertDiv.className = '';
                alertDiv.textContent = '';
            }, 5000);
        })
        .catch(error => {
            alertDiv.className = 'show error';
            alertDiv.textContent = '⚠️ Failed to send message. Please try again.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Message';
        });

    setTimeout(() => {
        alertDiv.className = 'show success';
        alertDiv.textContent = '✓ Thank you for your message! We will get back to you soon.';
        form.reset();
        submitBtn.disabled = false;
        submitBtn.textContent = 'Send Message';
        setTimeout(() => {
            alertDiv.className = '';
            alertDiv.textContent = '';
        }, 5000);
    }, 1000);
}

function showAuthModal() {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/page/')) {
        window.location.href = '../auth/login.php';
    } else {

        window.location.href = 'auth/login.php';
    }
}

function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

window.addEventListener('click', function (event) {
    const modal = document.getElementById('authModal');
    if (event.target === modal) {
        closeAuthModal();
    }
});
