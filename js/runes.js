/**
 * GoldOSRS – Runes Rain Animation
 * Renders RuneScape-style rune symbols streaming smoothly downward
 * using requestAnimationFrame for buttery performance.
 */

(function () {
    'use strict';

    // Runic character set (mix of actual Unicode runes + OSRS-feel glyphs)
    var RUNES = [
        '\u16A0', '\u16A2', '\u16AA', '\u16B1', '\u16B7',
        '\u16BC', '\u16C1', '\u16C3', '\u16CF', '\u16D2',
        '\u16D6', '\u16DA', '\u16DF', '\u16E3', '\u16E6',
        '\u16E9', '\u16EE', '\u16F0', '\u16F1', '\u16F2',
        '\u0D3F', '\u0D47', '\u0D4E', '\u0D38', '\u0D28',
        '\u16AB', '\u16AC', '\u16AD', '\u16AE', '\u16AF'
    ];

    var canvas, ctx;
    var columns = [];
    var fontSize = 16;
    var animId;

    function init() {
        canvas = document.getElementById('runesCanvas');
        if (!canvas) return;

        ctx = canvas.getContext('2d');
        resize();
        window.addEventListener('resize', resize);
        loop();
    }

    function resize() {
        canvas.width  = canvas.offsetWidth  || window.innerWidth;
        canvas.height = canvas.offsetHeight || window.innerHeight;
        var numCols = Math.floor(canvas.width / fontSize);
        columns = [];
        for (var i = 0; i < numCols; i++) {
            columns.push({
                x:     i * fontSize,
                y:     Math.random() * -canvas.height, // start above the canvas
                speed: 0.6 + Math.random() * 0.8,      // px per frame
                rune:  randomRune(),
                alpha: 0.3 + Math.random() * 0.5
            });
        }
    }

    function randomRune() {
        return RUNES[Math.floor(Math.random() * RUNES.length)];
    }

    var lastTime = 0;
    var TARGET_FPS = 30;
    var FRAME_INTERVAL = 1000 / TARGET_FPS;

    function loop(timestamp) {
        animId = requestAnimationFrame(loop);

        var elapsed = timestamp - lastTime;
        if (elapsed < FRAME_INTERVAL) return;
        lastTime = timestamp - (elapsed % FRAME_INTERVAL);

        // Fading trail
        ctx.fillStyle = 'rgba(13, 13, 13, 0.06)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.font = fontSize + 'px monospace';

        for (var i = 0; i < columns.length; i++) {
            var col = columns[i];

            // Draw rune
            ctx.fillStyle = 'rgba(200, 162, 39, ' + col.alpha + ')';
            ctx.fillText(col.rune, col.x, col.y);

            // Advance downward
            col.y += col.speed;

            // Occasionally change the rune character
            if (Math.random() < 0.02) {
                col.rune = randomRune();
            }

            // Reset when off-screen
            if (col.y > canvas.height + fontSize) {
                col.y     = -fontSize * (1 + Math.floor(Math.random() * 20));
                col.speed = 0.6 + Math.random() * 0.8;
                col.alpha = 0.3 + Math.random() * 0.5;
            }
        }
    }

    // Pause when tab is hidden to save resources
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            cancelAnimationFrame(animId);
        } else {
            lastTime = 0;
            loop(0);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
