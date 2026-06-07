Reverse Engineering - XOR Check
===============================

Di dalam instance ini ada sebuah binary ELF bernama `chall` (di /challenge).

    ./chall

Binary akan meminta sebuah flag dan hanya mencetak "Correct!" jika benar.

Catatan:
- Flag TIDAK tersimpan sebagai teks biasa. Menjalankan `strings chall | grep CTF`
  tidak akan menemukan apa pun.
- Program membandingkan input kamu menggunakan operasi XOR terhadap sebuah array
  byte yang ditanam (hardcoded):  enc[i] = flag[i] XOR key[i % panjang_key].
- Bongkar (disassemble) binary, temukan array `enc[]` dan `key[]`, lalu hitung
  enc[i] XOR key[i % 4] untuk memulihkan flag.

Format flag: CTF{...}
