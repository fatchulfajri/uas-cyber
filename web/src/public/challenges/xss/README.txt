Challenge: Reflected XSS
========================

Halaman "Product Search" memantulkan parameter `q` ke dalam HTML tanpa sanitasi
(reflected XSS).

Flag TIDAK ada di source code halaman. Flag disimpan di sebuah file:

    flag.txt   ->   /challenges/xss/flag.txt

Tujuan: manfaatkan XSS untuk MEMBACA isi flag.txt dari sisi browser dan
menampilkannya. Cukup membuka file secara langsung tidak dianggap "exploit" —
intinya adalah mengeksekusi JavaScript melalui parameter `q`.

Contoh payload yang perlu kamu rancang (lewat parameter q pada URL):

    ?q=<script>fetch('/challenges/xss/flag.txt').then(r=>r.text()).then(t=>alert(t))</script>

Saat alert menampilkan string berformat CTF{...}, halaman akan menandai bahwa
exploit berhasil. Submit flag tersebut pada dashboard.

Format flag: CTF{...}
