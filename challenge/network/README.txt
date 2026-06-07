Network PCAP - ICMP Exfiltration
================================

File `challenge.pcap` (di /challenge) berisi rekaman traffic jaringan.

Di dalamnya ada aktivitas yang mensimulasikan pencurian data (exfiltration):
sebuah host mengirim flag KARAKTER PER KARAKTER yang disembunyikan di dalam
payload paket ICMP echo request (ping, type 8).

Langkah penyelesaian:
1. Buka file dengan tcpdump / tshark / Wireshark, contoh:
       tcpdump -r challenge.pcap -A
2. Saring hanya ICMP echo request (icmp type 8).
3. Ambil 1 byte payload dari tiap paket, urut berdasarkan sequence.
4. Gabungkan menjadi flag.

Catatan: paket echo reply (type 0) hanya pengecoh (berisi "pong").

Format flag: CTF{...}
