<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-shell">
    <div class="landing-cursor-glow landing-cursor-glow-soft" id="landingCursorGlow" aria-hidden="true"></div>
    <div class="container min-vh-100 d-flex align-items-center py-5">
        <div class="row justify-content-center w-100">
            <div class="col-xl-10 col-xxl-9">
                <section class="landing-official-shell interactive-panel" data-landing-shell>
                    <div class="landing-official-bar" aria-hidden="true">
                        <span class="saffron"></span>
                        <span class="white"></span>
                        <span class="green"></span>
                    </div>

                    <div class="landing-official-grid">
                        <div class="landing-official-copy">
                            <div class="landing-govt-strip landing-govt-strip-official mb-4">
                                <div class="landing-govt-mark landing-govt-mark-official">
                                    <img src="assets/images/govt-india-mark.svg" alt="Government of India inspired emblem">
                                </div>
                                <div>
                                    <div class="landing-govt-title">Government of India</div>
                                    <div class="landing-govt-subtitle">National Digital Voting Access Portal</div>
                                </div>
                            </div>

                            <span class="landing-official-tag mb-3">Secure Voting Platform</span>
                            <h1 class="landing-official-title mb-3">Trusted online voting with identity-first protection.</h1>
                            <p class="landing-official-copytext mb-4">
                                Access a guided voting system designed to protect voter identity, support election discipline, and keep the ballot journey clear, secure, and accountable.
                            </p>

                            <div class="landing-official-slogan mb-4">
                                <span class="label">National slogan</span>
                                <strong>Meri Vote Meri Pehchan</strong>
                            </div>

                            <div class="landing-official-actions mb-4">
                                <a href="auth/user_login.php" class="btn btn-primary btn-lg px-4 landing-action">User Login</a>
                                <a href="auth/register.php" class="btn btn-outline-primary btn-lg px-4 landing-action">Register</a>
                                <a href="auth/admin_login.php" class="btn btn-dark btn-lg px-4 landing-action">Admin Login</a>
                                <a href="auth/admin_register.php" class="btn btn-outline-dark btn-lg px-4 landing-action">Admin Register</a>
                            </div>

                            <div class="landing-official-points">
                                <div class="landing-official-point">
                                    <strong>Face verification</strong>
                                    <span>Identity checks are built into registration and ballot access.</span>
                                </div>
                                <div class="landing-official-point">
                                    <strong>Controlled election window</strong>
                                    <span>Admins decide when voting opens and how long the ballot remains active.</span>
                                </div>
                                <div class="landing-official-point">
                                    <strong>Protected voting flow</strong>
                                    <span>Only verified users can reach the final vote submission stage.</span>
                                </div>
                            </div>
                        </div>

                        <div class="landing-official-visual" data-landing-visual>
                            <div class="official-visual-glow"></div>
                            <div class="official-visual-grid"></div>
                            <div class="official-visual-card main-card">
                                <div class="official-card-head">
                                    <span class="dot"></span>
                                    <span>Identity secured</span>
                                </div>
                                <img src="assets/images/govt-india-mark.svg" alt="Government of India inspired emblem" class="official-emblem-main">
                                <div class="official-card-footer">
                                    <span>Citizen authentication active</span>
                                </div>
                            </div>

                            <div class="official-mini-card top-card">
                                <strong>Verification Layer</strong>
                                <span>Face and OTP guided checkpoints</span>
                            </div>

                            <div class="official-mini-card bottom-card">
                                <strong>Meri Vote Meri Pehchan</strong>
                                <span>A trusted digital voting journey</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    var landingShell = document.querySelector('[data-landing-shell]');
    var landingVisual = document.querySelector('[data-landing-visual]');
    var cursorGlow = document.getElementById('landingCursorGlow');

    if (landingShell) {
        landingShell.addEventListener('mousemove', function (event) {
            var rect = landingShell.getBoundingClientRect();
            var x = event.clientX - rect.left;
            var y = event.clientY - rect.top;
            var rotateX = ((y / rect.height) - 0.5) * -3.5;
            var rotateY = ((x / rect.width) - 0.5) * 3.5;
            landingShell.style.setProperty('--rotate-x', rotateX.toFixed(2) + 'deg');
            landingShell.style.setProperty('--rotate-y', rotateY.toFixed(2) + 'deg');
            landingShell.style.setProperty('--spotlight-x', x.toFixed(0) + 'px');
            landingShell.style.setProperty('--spotlight-y', y.toFixed(0) + 'px');
        });

        landingShell.addEventListener('mouseleave', function () {
            landingShell.style.setProperty('--rotate-x', '0deg');
            landingShell.style.setProperty('--rotate-y', '0deg');
            landingShell.style.setProperty('--spotlight-x', '50%');
            landingShell.style.setProperty('--spotlight-y', '50%');
        });
    }

    if (landingVisual) {
        document.addEventListener('mousemove', function (event) {
            var rect = landingVisual.getBoundingClientRect();
            var x = (event.clientX - rect.left) / rect.width;
            var y = (event.clientY - rect.top) / rect.height;
            if (x >= 0 && x <= 1 && y >= 0 && y <= 1) {
                landingVisual.style.setProperty('--visual-shift-x', ((x - 0.5) * 14).toFixed(1) + 'px');
                landingVisual.style.setProperty('--visual-shift-y', ((y - 0.5) * 14).toFixed(1) + 'px');
            }
        });
    }

    if (cursorGlow && window.innerWidth > 575) {
        document.addEventListener('mousemove', function (event) {
            cursorGlow.style.transform = 'translate(' + (event.clientX - 120) + 'px, ' + (event.clientY - 120) + 'px)';
        });
    }
    </script>
    <script src="assets/js/theme-toggle.js"></script>
</body>
</html>
