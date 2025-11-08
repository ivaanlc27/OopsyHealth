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
    // simple client-side checks (size < 10MB and specific types)
    if (f.size > 10 * 1024 * 1024) {
      alert('File too large');
      e.preventDefault();
      return;
    }
    const allowed = ['image/png', 'image/jpeg', 'application/pdf', 'text/plain'];
    if (!allowed.includes(f.type)) {
      // but this is bypassable by changing file extension or using curl
      alert('Unexpected file type');
      e.preventDefault();
      return;
    }
    // allow submission
  });
});
