Cryptography - Weak RSA
=======================

File `output.txt` (di /challenge) berisi:

    n = <modulus>
    e = 65537
    c = [c0, c1, c2, ...]   # daftar blok ciphertext

RSA di sini sengaja lemah: n hanya hasil perkalian dua bilangan prima 24-bit,
sehingga sangat mudah difaktorkan (trial division, sympy.factorint, factordb.com,
dll).

Langkah penyelesaian:
1. Faktorkan n menjadi p dan q.
2. Hitung phi = (p-1)(q-1) lalu d = inverse(e, phi).
3. Dekripsi tiap blok:  m = pow(c, d, n).
4. Ubah tiap m kembali ke bytes (big-endian) dan gabungkan untuk membentuk flag.
   Flag dipecah menjadi blok-blok kecil (4 byte) sebelum dienkripsi.

Format flag: CTF{...}
