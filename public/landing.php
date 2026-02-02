<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ainstein - Coming Soon</title>
    <meta name="description" content="La piattaforma AI per dominare SEO e ADV. Presto disponibile.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= url('/favicon.svg') ?>">
    <meta name="theme-color" content="#0a0f1a">

    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            /* Brand colors */
            --primary-50: #e6f4f8;
            --primary-100: #cce9f1;
            --primary-200: #99d3e3;
            --primary-300: #66bdd5;
            --primary-400: #33a7c7;
            --primary-500: #006e96;
            --primary-600: #005577;
            --primary-700: #004d69;
            --primary-800: #003d54;
            --primary-900: #002e3f;

            /* Accent colors for effects */
            --accent-purple: #7c3aed;
            --accent-emerald: #10b981;

            /* Dark theme */
            --bg-dark: #0a0f1a;
            --bg-darker: #060a12;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-dark);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Animated gradient background with brand colors */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(ellipse at 20% 20%, rgba(0, 110, 150, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(0, 85, 119, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
            animation: gradientShift 15s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes gradientShift {
            0%, 100% {
                background:
                    radial-gradient(ellipse at 20% 20%, rgba(0, 110, 150, 0.2) 0%, transparent 50%),
                    radial-gradient(ellipse at 80% 80%, rgba(0, 85, 119, 0.15) 0%, transparent 50%),
                    radial-gradient(ellipse at 50% 50%, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
            }
            33% {
                background:
                    radial-gradient(ellipse at 80% 30%, rgba(0, 110, 150, 0.2) 0%, transparent 50%),
                    radial-gradient(ellipse at 20% 70%, rgba(0, 85, 119, 0.15) 0%, transparent 50%),
                    radial-gradient(ellipse at 60% 40%, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
            }
            66% {
                background:
                    radial-gradient(ellipse at 40% 80%, rgba(0, 110, 150, 0.2) 0%, transparent 50%),
                    radial-gradient(ellipse at 60% 20%, rgba(0, 85, 119, 0.15) 0%, transparent 50%),
                    radial-gradient(ellipse at 30% 60%, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
            }
        }

        /* Particle canvas */
        #particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        /* Grid overlay */
        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(rgba(0, 110, 150, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 110, 150, 0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: 2;
            pointer-events: none;
            animation: gridPulse 4s ease-in-out infinite;
        }

        @keyframes gridPulse {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 0.7; }
        }

        /* Main content */
        .content {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 2rem;
            max-width: 800px;
        }

        /* Logo container */
        .logo {
            width: 140px;
            height: 140px;
            margin: 0 auto 2.5rem;
            position: relative;
            animation: float 6s ease-in-out infinite;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 0 40px rgba(0, 110, 150, 0.6));
            position: relative;
            z-index: 2;
        }

        .logo::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 180%;
            height: 180%;
            background: radial-gradient(circle, rgba(0, 110, 150, 0.25) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
            z-index: 1;
        }

        .logo::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120%;
            height: 120%;
            border: 2px solid rgba(0, 110, 150, 0.2);
            border-radius: 50%;
            animation: ringPulse 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.4; }
            50% { transform: translate(-50%, -50%) scale(1.3); opacity: 0.7; }
        }

        @keyframes ringPulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0; }
        }

        /* Brand name */
        .brand-name {
            font-size: clamp(1rem, 3vw, 1.25rem);
            font-weight: 600;
            color: rgba(255, 255, 255, 0.6);
            letter-spacing: 0.4em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            position: relative;
        }

        /* Coming Soon text */
        .coming-soon {
            font-size: clamp(3rem, 10vw, 6rem);
            font-weight: 900;
            letter-spacing: -0.03em;
            line-height: 1.2;
            padding-bottom: 0.1em;
            margin-bottom: 2rem;
            background: linear-gradient(135deg,
                #ffffff 0%,
                var(--primary-300) 30%,
                var(--primary-500) 60%,
                var(--accent-emerald) 100%);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientText 6s ease infinite;
            position: relative;
        }

        .coming-soon::after {
            content: 'Coming Soon';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg,
                #ffffff 0%,
                var(--primary-300) 30%,
                var(--primary-500) 60%,
                var(--accent-emerald) 100%);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientText 6s ease infinite;
            filter: blur(30px);
            opacity: 0.5;
            z-index: -1;
        }

        @keyframes gradientText {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Tagline */
        .tagline {
            font-size: clamp(1rem, 2.5vw, 1.35rem);
            font-weight: 400;
            color: rgba(255, 255, 255, 0.55);
            line-height: 1.7;
            margin-bottom: 3rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .tagline strong {
            color: var(--primary-300);
            font-weight: 600;
        }

        /* Glowing orbs with brand colors */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            z-index: 0;
            animation: orbFloat 20s ease-in-out infinite;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: var(--primary-500);
            opacity: 0.12;
            top: -150px;
            right: -150px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: var(--primary-700);
            opacity: 0.1;
            bottom: -100px;
            left: -100px;
            animation-delay: -7s;
        }

        .orb-3 {
            width: 250px;
            height: 250px;
            background: var(--accent-emerald);
            opacity: 0.06;
            top: 40%;
            left: 60%;
            animation-delay: -14s;
        }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(40px, -40px) scale(1.15); }
            50% { transform: translate(-30px, 30px) scale(0.9); }
            75% { transform: translate(25px, 15px) scale(1.1); }
        }

        /* Status indicator */
        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            background: rgba(0, 110, 150, 0.1);
            border: 1px solid rgba(0, 110, 150, 0.25);
            border-radius: 100px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--primary-200);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .status:hover {
            background: rgba(0, 110, 150, 0.15);
            border-color: rgba(0, 110, 150, 0.4);
            transform: scale(1.02);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            background: var(--accent-emerald);
            border-radius: 50%;
            animation: statusPulse 2s ease-in-out infinite;
        }

        @keyframes statusPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 2rem;
            text-align: center;
            z-index: 10;
        }

        .footer p {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.25);
            font-weight: 400;
        }

        /* Subtle scan line effect */
        .scanline {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                transparent 50%,
                rgba(0, 0, 0, 0.015) 50%
            );
            background-size: 100% 4px;
            z-index: 100;
            pointer-events: none;
            opacity: 0.5;
        }

        /* Mobile adjustments */
        @media (max-width: 640px) {
            .logo {
                width: 100px;
                height: 100px;
            }

            .orb-1 { width: 250px; height: 250px; }
            .orb-2 { width: 200px; height: 200px; }
            .orb-3 { width: 120px; height: 120px; }

            .tagline {
                padding: 0 1rem;
            }
        }

        /* Glitch effect on hover */
        .coming-soon:hover {
            animation: gradientText 6s ease infinite, glitch 0.3s ease;
        }

        @keyframes glitch {
            0% { transform: translate(0); }
            20% { transform: translate(-3px, 3px); }
            40% { transform: translate(-3px, -3px); }
            60% { transform: translate(3px, 3px); }
            80% { transform: translate(3px, -3px); }
            100% { transform: translate(0); }
        }

        /* Mouse interaction hint */
        #particles {
            pointer-events: auto;
            cursor: default;
        }
    </style>
</head>
<body>
    <!-- Background effects -->
    <div class="bg-gradient"></div>
    <div class="grid-overlay"></div>
    <div class="scanline"></div>

    <!-- Floating orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Particles -->
    <canvas id="particles"></canvas>

    <!-- Main content -->
    <main class="content">
        <div class="logo">
            <img src="<?= url('/assets/images/logo-ainstein-square.png') ?>" alt="Ainstein">
        </div>

        <div class="brand-name">Ainstein</div>

        <h1 class="coming-soon">Coming Soon</h1>


        <div class="status">
            <span class="status-dot"></span>
            <span>In sviluppo</span>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Ainstein. Tutti i diritti riservati.</p>
    </footer>

    <!-- Particles animation -->
    <script>
        const canvas = document.getElementById('particles');
        const ctx = canvas.getContext('2d');

        let particles = [];
        const particleCount = 70;
        const connectionDistance = 130;

        // Brand color for particles
        const particleColor = { r: 0, g: 110, b: 150 }; // #006e96

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        function createParticle() {
            return {
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                size: Math.random() * 2.5 + 1,
                opacity: Math.random() * 0.5 + 0.3
            };
        }

        function init() {
            resize();
            particles = [];
            for (let i = 0; i < particleCount; i++) {
                particles.push(createParticle());
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            particles.forEach((p, i) => {
                // Update position
                p.x += p.vx;
                p.y += p.vy;

                // Wrap around edges
                if (p.x < 0) p.x = canvas.width;
                if (p.x > canvas.width) p.x = 0;
                if (p.y < 0) p.y = canvas.height;
                if (p.y > canvas.height) p.y = 0;

                // Draw particle with brand color
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(${particleColor.r}, ${particleColor.g}, ${particleColor.b}, ${p.opacity})`;
                ctx.fill();

                // Draw connections
                particles.slice(i + 1).forEach(p2 => {
                    const dx = p.x - p2.x;
                    const dy = p.y - p2.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < connectionDistance) {
                        const opacity = (1 - distance / connectionDistance) * 0.25;
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(p2.x, p2.y);
                        ctx.strokeStyle = `rgba(${particleColor.r}, ${particleColor.g}, ${particleColor.b}, ${opacity})`;
                        ctx.lineWidth = 0.8;
                        ctx.stroke();
                    }
                });
            });

            requestAnimationFrame(animate);
        }

        window.addEventListener('resize', resize);
        init();
        animate();

        // Mouse interaction - particles react to cursor
        let mouse = { x: null, y: null };

        canvas.addEventListener('mousemove', (e) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;

            particles.forEach(p => {
                const dx = mouse.x - p.x;
                const dy = mouse.y - p.y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < 120) {
                    const force = (120 - distance) / 120;
                    p.vx -= (dx / distance) * force * 0.03;
                    p.vy -= (dy / distance) * force * 0.03;
                }
            });
        });

        // Touch support for mobile
        canvas.addEventListener('touchmove', (e) => {
            if (e.touches.length > 0) {
                mouse.x = e.touches[0].clientX;
                mouse.y = e.touches[0].clientY;

                particles.forEach(p => {
                    const dx = mouse.x - p.x;
                    const dy = mouse.y - p.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < 120) {
                        const force = (120 - distance) / 120;
                        p.vx -= (dx / distance) * force * 0.03;
                        p.vy -= (dy / distance) * force * 0.03;
                    }
                });
            }
        });
    </script>
</body>
</html>
