#include <stdio.h>
#include <string.h>

/*
 * The flag is checked with a bitwise XOR against a hardcoded byte array, so the
 * plaintext flag never appears in the compiled binary:
 *
 *     enc[i] = flag[i] ^ key[i % sizeof(key)]
 *
 * Running `strings`/`grep CTF` on the binary reveals nothing. To recover the
 * flag, extract enc[] and key[] from the binary and compute enc[i] ^ key[i % 4].
 */

int main(void) {
    unsigned char enc[] = {
        0x50, 0x63, 0x04, 0x21, 0x77, 0x04, 0x21, 0x6A,
        0x7E, 0x47, 0x73, 0x36, 0x20, 0x68, 0x20, 0x6B,
        0x7D, 0x03, 0x30, 0x23, 0x4C, 0x03, 0x2C, 0x3E,
        0x4C, 0x54, 0x72, 0x34, 0x62, 0x42, 0x71, 0x28,
        0x6E
    };
    unsigned char key[] = { 0x13, 0x37, 0x42, 0x5A };
    size_t n = sizeof(enc);

    char input[128];
    printf("Enter flag: ");
    if (scanf("%127s", input) != 1) {
        return 1;
    }

    if (strlen(input) != n) {
        printf("Wrong!\n");
        return 0;
    }

    for (size_t i = 0; i < n; i++) {
        if (((unsigned char)input[i] ^ key[i % sizeof(key)]) != enc[i]) {
            printf("Wrong!\n");
            return 0;
        }
    }

    printf("Correct! That's the flag.\n");
    return 0;
}
