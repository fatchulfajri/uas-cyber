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
        0x50, 0x63, 0x04, 0x21, 0x60, 0x06, 0x2F, 0x2A,
        0x7F, 0x04, 0x1D, 0x28, 0x20, 0x41, 0x71, 0x28,
        0x60, 0x04, 0x1D, 0x69, 0x7F, 0x51, 0x3F
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
