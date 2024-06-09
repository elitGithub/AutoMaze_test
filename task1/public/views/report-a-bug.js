// polyfill for crypto.randomUUID, because we may not work under a secure environment like HTTPS.
if (typeof crypto.randomUUID !== 'function') {
  crypto.randomUUID = function () {
    const hex = (n) => {
      const str = n.toString(16);
      return str.length === 1 ? '0' + str : str;
    };

    // Generate random bytes
    const randomBytes = new Uint8Array(16);
    for (let i = 0; i < 16; i++) {
      randomBytes[i] = Math.floor(Math.random() * 256);
    }

    // Set the version to 4 (random UUID)
    randomBytes[6] = (randomBytes[6] & 0x0f) | 0x40;
    randomBytes[8] = (randomBytes[8] & 0x3f) | 0x80;

    // Convert the random bytes to a UUID string
    return (
      hex(randomBytes[0]) + hex(randomBytes[1]) +
      hex(randomBytes[2]) + hex(randomBytes[3]) + '-' +
      hex(randomBytes[4]) + hex(randomBytes[5]) + '-' +
      hex(randomBytes[6]) + hex(randomBytes[7]) + '-' +
      hex(randomBytes[8]) + hex(randomBytes[9]) + '-' +
      hex(randomBytes[10]) + hex(randomBytes[11]) +
      hex(randomBytes[12]) + hex(randomBytes[13]) +
      hex(randomBytes[14]) + hex(randomBytes[15])
    );
  };
}
let storedId = localStorage.getItem('id');
if (!storedId) {
  storedId = crypto.randomUUID();
  localStorage.setItem('id', storedId);
}

document.getElementById('user_id').value = storedId;
