document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('#upload-form');
  if (!form) return;
  form.addEventListener('submit', function (e) {
    const fileInput = document.querySelector('#file');
    if (!fileInput || !fileInput.files.length) {
      alert('Select a file');
      e.preventDefault();
      return;
    }
    const f = fileInput.files[0];
    // simple client-side checks (size < 2MB and specific types)
    if (f.size > 2 * 1024 * 1024) {
      alert('File too large (client-side check)');
      e.preventDefault();
      return;
    }
    const allowed = ['image/png', 'image/jpeg', 'application/pdf', 'text/plain'];
    if (!allowed.includes(f.type) && f.name.indexOf('.test') === -1) {
      // but this is bypassable by changing file extension or using curl
      alert('Unexpected file type (client-side)');
      e.preventDefault();
      return;
    }
    // allow submission
  });
});
