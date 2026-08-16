{{--
    Sahifa yuklanganda favicon bir marta "pop-in" animatsiyasi bilan
    paydo bo'ladi (diqqatni tortish uchun), so'ng statik holatda qoladi.
    Barcha zamonaviy brauzerlarda ishlaydi, script mavjud bo'lmasa ham
    static favicon.ico/PNG variantlar allaqachon <head> orqali ishlaydi.
--}}
<script>
(function () {
    if (typeof document === 'undefined' || !window.requestAnimationFrame) return;

    var SIZE = 64;
    var BLUE = '#0065ca';
    var canvas = document.createElement('canvas');
    canvas.width = SIZE;
    canvas.height = SIZE;
    var ctx = canvas.getContext('2d');
    if (!ctx || !ctx.roundRect) return;

    function drawIcon(scale) {
        ctx.clearRect(0, 0, SIZE, SIZE);
        ctx.save();
        ctx.translate(SIZE / 2, SIZE / 2);
        ctx.scale(scale, scale);
        ctx.translate(-SIZE / 2, -SIZE / 2);

        // Badge
        ctx.fillStyle = BLUE;
        ctx.beginPath();
        ctx.roundRect(3, 3, SIZE - 6, SIZE - 6, 15);
        ctx.fill();

        // Book pages (white, with a shallow spine notch at top-center)
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.roundRect(12, 19, 40, 26, 3);
        ctx.fill();

        ctx.fillStyle = BLUE;
        ctx.beginPath();
        ctx.moveTo(29, 19);
        ctx.lineTo(32, 22);
        ctx.lineTo(35, 19);
        ctx.closePath();
        ctx.fill();

        ctx.fillRect(31.5, 21, 1, 22);

        ctx.restore();
    }

    var link = document.querySelector('link[rel="icon"][type="image/png"]') || document.querySelector('link[rel="icon"]');
    if (!link) return;

    var start = null;
    var duration = 700;

    function easeOutBack(t) {
        var c1 = 1.70158, c3 = c1 + 1;
        return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2);
    }

    function frame(ts) {
        if (start === null) start = ts;
        var elapsed = ts - start;
        var t = Math.min(elapsed / duration, 1);
        var scale = 0.4 + easeOutBack(t) * 0.6;
        drawIcon(scale);
        link.href = canvas.toDataURL('image/png');

        if (t < 1) {
            window.requestAnimationFrame(frame);
        } else {
            drawIcon(1);
            link.href = canvas.toDataURL('image/png');
        }
    }

    window.requestAnimationFrame(frame);
})();
</script>
