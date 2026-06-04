(function () {
    function readFilePreview(file, callback) {
        if (!file || !window.FileReader) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function (event) {
            callback(event.target.result);
        };
        reader.readAsDataURL(file);
    }

    document.querySelectorAll('.pa-branding-file').forEach(function (input) {
        input.addEventListener('change', function () {
            var previewId = input.getAttribute('data-preview');
            var preview = previewId ? document.getElementById(previewId) : null;
            if (!preview || !input.files || !input.files[0]) {
                return;
            }
            readFilePreview(input.files[0], function (src) {
                var isFavicon = input.getAttribute('data-favicon') === '1';
                preview.innerHTML = '<img src="' + src + '" alt="Preview"' + (isFavicon ? ' style="width:32px;height:32px;object-fit:contain;"' : '') + ' />';
            });
        });
    });
})();
