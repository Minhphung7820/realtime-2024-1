import CryptoJS from "crypto-js";
export function encodeQueryParams(params) {
    return btoa(JSON.stringify(params));
}

export function decodeQueryParams(encodedParams) {
    try {
        return JSON.parse(atob(encodedParams));
    } catch (e) {
        console.error("Invalid query parameter format:", e);
        return null;
    }
}

export function formatTimeDifference(lastActive) {
    const now = new Date();
    const lastActiveDate = new Date(lastActive);
    const diffSeconds = Math.floor((now - lastActiveDate) / 1000); // Chênh lệch giây

    if (diffSeconds < 60) {
        return 'Vừa truy cập';
    } else if (diffSeconds < 3600) {
        const minutes = Math.floor(diffSeconds / 60);
        return `${minutes} phút trước`;
    } else if (diffSeconds < 86400) {
        const hours = Math.floor(diffSeconds / 3600);
        return `${hours} giờ trước`;
    } else if (diffSeconds < 604800) {
        const days = Math.floor(diffSeconds / 86400);
        return `${days} ngày trước`;
    } else if (diffSeconds < 2592000) {
        const weeks = Math.floor(diffSeconds / 604800);
        return `${weeks} tuần trước`;
    } else {
        const months = Math.floor(diffSeconds / 2592000);
        return `${months} tháng trước`;
    }
}

export function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

export function getFlagEmoji(countryCode) {
    // Validate the input to be exactly two characters long and all alphabetic
    if (!countryCode || countryCode.length !== 2 || !/^[a-zA-Z]+$/.test(countryCode)) {
        return '🏳️'; // White Flag Emoji for unknown or invalid country codes
    }

    // Convert the country code to uppercase to match the regional indicator symbols
    const code = countryCode.toUpperCase();

    // Calculate the offset for the regional indicator symbols
    const offset = 127397;

    // Convert each letter in the country code to its corresponding regional indicator symbol
    const flag = Array.from(code).map(letter => String.fromCodePoint(letter.charCodeAt(0) + offset)).join('');

    return flag;
}
// Hàm tạo cặp khóa public/private
export async function generateKeyPair() {
    const keyPair = await crypto.subtle.generateKey({
        name: "RSA-OAEP",
        modulusLength: 2048, // Độ dài khóa (2048 bit - tiêu chuẩn)
        publicExponent: new Uint8Array([1, 0, 1]), // 65537
        hash: { name: "SHA-256" }, // Thuật toán băm
    },
        true, // Cho phép export key
        ["encrypt", "decrypt"] // Public key dùng để mã hóa, private key dùng để giải mã
    );

    // Xuất public key ở định dạng 'spki' (SubjectPublicKeyInfo)
    const publicKey = await crypto.subtle.exportKey("spki", keyPair.publicKey);

    // Xuất private key ở định dạng 'pkcs8' (Private Key Cryptographic Standard #8)
    const privateKey = await crypto.subtle.exportKey("pkcs8", keyPair.privateKey);

    // Encode Base64 để lưu trữ dưới dạng chuỗi
    return {
        publicKey: btoa(String.fromCharCode(...new Uint8Array(publicKey))),
        privateKey: btoa(String.fromCharCode(...new Uint8Array(privateKey))),
    };
}

export function generateDeviceId() {
    const deviceInfo = `${navigator.userAgent}`;
    return deviceInfo; // Encode Base64 để lưu trữ
}

/**
 * Mã hóa private key bằng Recovery Key
 * @param {string} privateKey - Private key gốc (dạng Base64 hoặc chuỗi)
 * @param {string} recoveryKey - Recovery Key của người dùng
 * @returns {string} - Private key đã mã hóa bằng Recovery Key
 */
export function encryptPrivateKeyWithRecoveryKey(privateKey, recoveryKey) {
    // Mã hóa private key bằng AES với Recovery Key
    const encryptedPrivateKey = CryptoJS.AES.encrypt(privateKey, recoveryKey).toString();
    return encryptedPrivateKey;
}

/**
 * Hàm mã hóa privateKey bằng mã PIN
 * @param {string} privateKey - Private key dưới dạng chuỗi Base64
 * @param {string} pin - Mã PIN của người dùng
 * @returns {string} - Private key đã mã hóa bằng AES
 */
export function encryptPrivateKeyWithPin(privateKey, pin) {
    // Sử dụng AES để mã hóa privateKey bằng mã PIN
    const encryptedPrivateKey = CryptoJS.AES.encrypt(privateKey, pin).toString();
    return encryptedPrivateKey;
}

/**
 * Giải mã private key bằng mã PIN
 * @param {string} encryptedPrivateKey - Private key đã mã hóa bằng mã PIN
 * @param {string} pin - Mã PIN của người dùng
 * @returns {string|null} - Private key nguyên bản hoặc null nếu mã PIN sai
 */
export function decryptPrivateKeyWithPin(encryptedPrivateKey, pin) {
    try {
        // Giải mã private key
        const bytes = CryptoJS.AES.decrypt(encryptedPrivateKey, pin);
        const privateKey = bytes.toString(CryptoJS.enc.Utf8);

        // Kiểm tra xem kết quả có hợp lệ hay không (không phải chuỗi rỗng)
        if (!privateKey) {
            throw new Error("Giải mã thất bại: Mã PIN không hợp lệ hoặc dữ liệu bị hỏng.");
        }

        return privateKey;
    } catch (error) {
        console.error("Giải mã thất bại:", error.message);
        return null; // Trả về null nếu giải mã thất bại
    }
}

/**
 * Giải mã private key bằng Recovery Key
 * @param {string} encryptedPrivateKey - Private key đã mã hóa bằng Recovery Key
 * @param {string} recoveryKey - Recovery Key của người dùng
 * @returns {string|null} - Private key nguyên bản hoặc null nếu Recovery Key sai
 */
export function decryptPrivateKeyWithRecoveryKey(encryptedPrivateKey, recoveryKey) {
    try {
        // Giải mã private key
        const bytes = CryptoJS.AES.decrypt(encryptedPrivateKey, recoveryKey);
        const privateKey = bytes.toString(CryptoJS.enc.Utf8);

        // Kiểm tra xem kết quả có hợp lệ hay không (không phải chuỗi rỗng)
        if (!privateKey) {
            throw new Error("Giải mã thất bại: Recovery Key không hợp lệ hoặc dữ liệu bị hỏng.");
        }

        return privateKey;
    } catch (error) {
        console.error("Giải mã thất bại:", error.message);
        return null; // Trả về null nếu giải mã thất bại
    }
}

// Hàm importPublicKey
export async function importPublicKey(base64Key) {
    const pemKey = `-----BEGIN PUBLIC KEY-----\n${base64Key.match(/.{1,64}/g).join("\n")}\n-----END PUBLIC KEY-----`;
    const cleanBase64 = pemKey
        .replace(/-----BEGIN PUBLIC KEY-----/, "")
        .replace(/-----END PUBLIC KEY-----/, "")
        .replace(/\n/g, "");
    const binaryDer = Uint8Array.from(atob(cleanBase64), (char) => char.charCodeAt(0));
    return await crypto.subtle.importKey(
        "spki",
        binaryDer, {
        name: "RSA-OAEP",
        hash: { name: "SHA-256" },
    },
        true, ["encrypt"]
    );
}

export async function importPrivateKey(base64Key) {
    const pemKey = `-----BEGIN PRIVATE KEY-----\n${base64Key.match(/.{1,64}/g).join("\n")}\n-----END PRIVATE KEY-----`;
    const cleanBase64 = pemKey
        .replace(/-----BEGIN PRIVATE KEY-----/, "")
        .replace(/-----END PRIVATE KEY-----/, "")
        .replace(/\n/g, "");
    const binaryDer = Uint8Array.from(atob(cleanBase64), (char) => char.charCodeAt(0));
    return await crypto.subtle.importKey(
        "pkcs8",
        binaryDer, {
        name: "RSA-OAEP",
        hash: { name: "SHA-256" },
    },
        true, ["decrypt"]
    );
}
/**
 * Tạo mã Master Key ngẫu nhiên
 * @returns {string} - Master Key (dạng chuỗi Base64)
 */
export function generateMasterKey() {
    // Tạo Master Key ngẫu nhiên (256-bit)
    const randomBytes = crypto.getRandomValues(new Uint8Array(32)); // 32 bytes = 256 bits
    return btoa(String.fromCharCode(...randomBytes)); // Chuyển thành Base64 để lưu trữ
}

/**
 * Mã hóa Private Key bằng Master Key
 * @param {string} privateKey - Private Key (dạng Base64)
 * @param {string} masterKey - Master Key (dạng Base64)
 * @returns {string} - Private Key đã mã hóa (dạng Base64)
 */
export function encryptPrivateKeyWithMasterKey(privateKey, masterKey) {
    // Mã hóa Private Key bằng AES với Master Key
    const encryptedPrivateKey = CryptoJS.AES.encrypt(privateKey, masterKey).toString();
    return encryptedPrivateKey; // Trả về chuỗi Base64
}

/**
 * Giải mã Private Key bằng Master Key
 * @param {string} encryptedPrivateKey - Private Key đã mã hóa (dạng Base64)
 * @param {string} masterKey - Master Key (dạng Base64)
 * @returns {string|null} - Private Key gốc hoặc null nếu giải mã thất bại
 */
export function decryptPrivateKeyWithMasterKey(encryptedPrivateKey, masterKey) {
    try {
        // Giải mã Private Key bằng AES với Master Key
        const bytes = CryptoJS.AES.decrypt(encryptedPrivateKey, masterKey);
        const privateKey = bytes.toString(CryptoJS.enc.Utf8);

        // Kiểm tra kết quả
        if (!privateKey) {
            throw new Error("Giải mã thất bại: Master Key không hợp lệ hoặc dữ liệu bị hỏng.");
        }

        return privateKey; // Trả về Private Key gốc
    } catch (error) {
        console.error("Giải mã thất bại:", error.message);
        return null; // Trả về null nếu giải mã thất bại
    }
}

/**
 * Mã hóa Master Key bằng mã PIN
 * @param {string} masterKey - Master Key (dạng Base64)
 * @param {string} pin - Mã PIN của người dùng
 * @returns {string} - Master Key đã mã hóa (dạng Base64)
 */
export function encryptMasterKeyWithPin(masterKey, pin) {
    // Sử dụng AES để mã hóa Master Key bằng mã PIN
    const encryptedMasterKey = CryptoJS.AES.encrypt(masterKey, pin).toString();
    return encryptedMasterKey;
}

/**
 * Giải mã Master Key bằng mã PIN
 * @param {string} encryptedMasterKey - Master Key đã mã hóa (dạng Base64)
 * @param {string} pin - Mã PIN của người dùng
 * @returns {string|null} - Master Key gốc hoặc null nếu giải mã thất bại
 */
export function decryptMasterKeyWithPin(encryptedMasterKey, pin) {
    try {
        // Giải mã Master Key bằng AES với mã PIN
        const bytes = CryptoJS.AES.decrypt(encryptedMasterKey, pin);
        const masterKey = bytes.toString(CryptoJS.enc.Utf8);

        // Kiểm tra kết quả
        if (!masterKey) {
            throw new Error("Giải mã thất bại: Mã PIN không hợp lệ hoặc dữ liệu bị hỏng.");
        }

        return masterKey; // Trả về Master Key gốc
    } catch (error) {
        console.error("Giải mã thất bại:", error.message);
        return null; // Trả về null nếu giải mã thất bại
    }
}

/**
 * Tạo Recovery Key ngẫu nhiên
 * @returns {string} - Recovery Key (dạng chuỗi Base64)
 */
export function generateRecoveryKey() {
    // Tạo Recovery Key ngẫu nhiên (256-bit)
    const randomBytes = crypto.getRandomValues(new Uint8Array(32)); // 32 bytes = 256 bits
    return btoa(String.fromCharCode(...randomBytes)); // Chuyển thành Base64 để lưu trữ
}

/**
 * Mã hóa Master Key bằng Recovery Key
 * @param {string} masterKey - Master Key (dạng Base64)
 * @param {string} recoveryKey - Recovery Key (dạng Base64)
 * @returns {string} - Master Key đã mã hóa (dạng Base64)
 */
export function encryptMasterKeyWithRecovery(masterKey, recoveryKey) {
    // Sử dụng AES để mã hóa Master Key bằng Recovery Key
    const encryptedMasterKey = CryptoJS.AES.encrypt(masterKey, recoveryKey).toString();
    return encryptedMasterKey;
}

/**
 * Giải mã Master Key bằng Recovery Key
 * @param {string} encryptedMasterKey - Master Key đã mã hóa (dạng Base64)
 * @param {string} recoveryKey - Recovery Key (dạng Base64)
 * @returns {string|null} - Master Key gốc hoặc null nếu giải mã thất bại
 */
export function decryptMasterKeyWithRecovery(encryptedMasterKey, recoveryKey) {
    try {
        // Giải mã Master Key bằng AES với Recovery Key
        const bytes = CryptoJS.AES.decrypt(encryptedMasterKey, recoveryKey);
        const masterKey = bytes.toString(CryptoJS.enc.Utf8);

        // Kiểm tra kết quả
        if (!masterKey) {
            throw new Error("Giải mã thất bại: Recovery Key không hợp lệ hoặc dữ liệu bị hỏng.");
        }

        return masterKey; // Trả về Master Key gốc
    } catch (error) {
        console.error("Giải mã thất bại:", error.message);
        return null; // Trả về null nếu giải mã thất bại
    }
}

export async function checkKeyPair(privateKeyPem, publicKeyPem) {
    try {
        // Parse Public Key
        const publicKey = await window.crypto.subtle.importKey(
            "spki",
            convertPemToBinary(publicKeyPem), { name: "RSA-PSS", hash: { name: "SHA-256" } },
            true, ["verify"]
        );

        // Parse Private Key
        const privateKey = await window.crypto.subtle.importKey(
            "pkcs8",
            convertPemToBinary(privateKeyPem), { name: "RSA-PSS", hash: { name: "SHA-256" } },
            true, ["sign"]
        );

        // Sign a message using the private key
        const data = new TextEncoder().encode("Test Message");
        const signature = await window.crypto.subtle.sign({ name: "RSA-PSS", saltLength: 32 },
            privateKey,
            data
        );

        // Verify the signature using the public key
        const isVerified = await window.crypto.subtle.verify({ name: "RSA-PSS", saltLength: 32 },
            publicKey,
            signature,
            data
        );

        return isVerified;
    } catch (error) {
        console.error("Error checking key pair:", error);
        return false;
    }
}

// Helper function to convert PEM to binary
function convertPemToBinary(pem) {
    const base64 = pem
        .replace(/-----BEGIN [^-]+-----/, "")
        .replace(/-----END [^-]+-----/, "")
        .replace(/\s+/g, "");
    return Uint8Array.from(atob(base64), (c) => c.charCodeAt(0));
}

export async function encryptMessageWithPublicKey(message, publicKey) {
    const aesKey = crypto.getRandomValues(new Uint8Array(32)); // Tạo AES Key
    const iv = crypto.getRandomValues(new Uint8Array(12)); // Tạo Initialization Vector (IV)
    const aesCipher = await crypto.subtle.encrypt({
        name: "AES-GCM",
        iv,
    },
        await crypto.subtle.importKey("raw", aesKey, "AES-GCM", false, ["encrypt"]),
        new TextEncoder().encode(message)
    );
    const encryptedAesKey = await crypto.subtle.encrypt({
        name: "RSA-OAEP",
    },
        publicKey,
        aesKey
    );
    return {
        encryptedMessage: btoa(String.fromCharCode(...new Uint8Array(aesCipher))),
        encryptedKey: btoa(String.fromCharCode(...new Uint8Array(encryptedAesKey))),
        iv: btoa(String.fromCharCode(...iv)),
    };
}

export async function decryptMessageWithPrivateKey(encryptedData, privateKey) {
    const { encryptedMessage, encryptedKey, iv } = encryptedData;

    const aesKey = await crypto.subtle.decrypt({
        name: "RSA-OAEP",
    },
        privateKey,
        new Uint8Array(atob(encryptedKey).split("").map((c) => c.charCodeAt(0)))
    );

    const decryptedMessage = await crypto.subtle.decrypt({
        name: "AES-GCM",
        iv: new Uint8Array(atob(iv).split("").map((c) => c.charCodeAt(0))),
    },
        await crypto.subtle.importKey("raw", aesKey, "AES-GCM", false, ["decrypt"]),
        new Uint8Array(atob(encryptedMessage).split("").map((c) => c.charCodeAt(0)))
    );

    return new TextDecoder().decode(decryptedMessage);
}

export async function encryptMessageWithGroupKey(message, groupKey) {
    // Decode Group Key từ Base64
    const rawGroupKey = new Uint8Array(atob(groupKey).split("").map(c => c.charCodeAt(0)));

    // Tạo Initialization Vector (IV) ngẫu nhiên
    const iv = crypto.getRandomValues(new Uint8Array(12));

    // Mã hóa tin nhắn bằng AES-GCM
    const encryptedMessage = await crypto.subtle.encrypt({
        name: "AES-GCM",
        iv,
    },
        await crypto.subtle.importKey("raw", rawGroupKey, "AES-GCM", false, ["encrypt"]),
        new TextEncoder().encode(message)
    );

    return {
        encryptedMessage: btoa(String.fromCharCode(...new Uint8Array(encryptedMessage))), // Base64
        iv: btoa(String.fromCharCode(...iv)), // Base64
    };
}

export async function decryptMessageWithGroupKey(encryptedData, groupKey) {
    const { encryptedMessage, iv } = encryptedData;

    // Decode Group Key và IV từ Base64
    const rawGroupKey = new Uint8Array(atob(groupKey).split("").map(c => c.charCodeAt(0)));
    const rawIv = new Uint8Array(atob(iv).split("").map(c => c.charCodeAt(0)));

    // Giải mã tin nhắn bằng AES-GCM
    const decryptedMessage = await crypto.subtle.decrypt({
        name: "AES-GCM",
        iv: rawIv,
    },
        await crypto.subtle.importKey("raw", rawGroupKey, "AES-GCM", false, ["decrypt"]),
        new Uint8Array(atob(encryptedMessage).split("").map(c => c.charCodeAt(0)))
    );

    return new TextDecoder().decode(decryptedMessage);
}

export async function generateGroupKey() {
    const key = await crypto.subtle.generateKey({
        name: "AES-GCM",
        length: 256,
    },
        true, ["encrypt", "decrypt"]
    );
    return key;
}

export async function encryptFileWithGroupKey(groupKey, fileData, fileName = null) {
    const iv = crypto.getRandomValues(new Uint8Array(12)); // Random IV

    // Kết hợp tên file và nội dung file thành một JSON string
    const fileObject = {
        name: fileName, // Tên file
        content: Array.from(new Uint8Array(fileData)), // Chuyển nội dung file thành mảng số
    };
    const fileString = JSON.stringify(fileObject);

    // Mã hóa dữ liệu file (tên + nội dung)
    const encryptedData = await crypto.subtle.encrypt({
        name: "AES-GCM",
        iv,
    },
        groupKey,
        new TextEncoder().encode(fileString) // Convert string to ArrayBuffer
    );

    const encryptedUint8Array = new Uint8Array(encryptedData);
    const combinedData = new Blob([iv, encryptedUint8Array], { type: "application/octet-stream" });

    return combinedData; // Trả về Blob chứa IV và dữ liệu mã hóa
}

export async function decryptFileWithGroupKey(encryptedBlob, groupKey) {
    try {
        const arrayBuffer = await encryptedBlob.arrayBuffer();

        // Tách IV và dữ liệu mã hóa
        const iv = new Uint8Array(arrayBuffer.slice(0, 12));
        const encryptedData = arrayBuffer.slice(12);

        // Giải mã dữ liệu
        const decryptedData = await crypto.subtle.decrypt({
            name: "AES-GCM",
            iv,
        }, groupKey, encryptedData);

        // Convert ArrayBuffer thành string JSON
        const fileString = new TextDecoder().decode(decryptedData);

        // Parse JSON để lấy tên và nội dung file
        const fileObject = JSON.parse(fileString);

        const fileName = fileObject.name; // Lấy tên file
        const fileContent = new Uint8Array(fileObject.content); // Lấy nội dung file

        return { fileName, fileContent }; // Trả về đối tượng chứa tên và nội dung file
    } catch (error) {
        throw error; // Báo lỗi nếu có vấn đề
    }
}

export async function encryptGroupKeyWithPublicKey(groupKey, publicKey) {
    const exportedKey = await crypto.subtle.exportKey("raw", groupKey);
    const encryptedGroupKey = await crypto.subtle.encrypt({
        name: "RSA-OAEP",
    },
        publicKey,
        exportedKey
    );
    return encryptedGroupKey;
}

export async function decryptGroupKeyWithPrivateKey(encryptedGroupKey, privateKey) {
    const decryptedGroupKeyRaw = await crypto.subtle.decrypt({
        name: "RSA-OAEP",
    },
        privateKey,
        encryptedGroupKey
    );
    const groupKey = await crypto.subtle.importKey(
        "raw",
        decryptedGroupKeyRaw, {
        name: "AES-GCM",
    },
        true, ["encrypt", "decrypt"]
    );
    return groupKey;
}

export async function arrayBufferToBase64(buffer) {
    let binary = '';
    const bytes = new Uint8Array(buffer);
    const len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary); // Encode to Base64
}

export async function base64ToArrayBuffer(base64) {
    const binaryString = atob(base64); // Decode Base64
    const len = binaryString.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer; // Trả về ArrayBuffer
}

export async function testEncryptionDecryption() {
    try {
        // Tạo groupKey
        const groupKey = await crypto.subtle.generateKey({ name: "AES-GCM", length: 256 },
            true, ["encrypt", "decrypt"]
        );

        // Dữ liệu đầu vào (ArrayBuffer)
        const originalData = new TextEncoder().encode("This is a test file").buffer;

        // Mã hóa
        const encryptedBlob = await encryptFileWithGroupKey(groupKey, originalData);
        console.log("Encrypted Blob:", encryptedBlob);

        // Giải mã
        const decryptedData = await decryptFileWithGroupKey(encryptedBlob, groupKey);
        console.log("Decrypted Data:", new TextDecoder().decode(decryptedData));

        // So sánh dữ liệu gốc và dữ liệu giải mã
        if (new TextDecoder().decode(decryptedData) === "This is a test file") {
            console.log("Encryption and decryption work correctly!");
        } else {
            console.error("Decryption result does not match the original data!");
        }
    } catch (error) {
        console.error("Error in encryption/decryption:", error);
    }
}

export async function compressImage(file, quality = 0.7, maxWidth = 1024) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = function (event) {
            const img = new Image();
            img.src = event.target.result;

            img.onload = function () {
                const canvas = document.createElement("canvas");
                const ctx = canvas.getContext("2d");

                const scaleSize = maxWidth / img.width;
                canvas.width = maxWidth;
                canvas.height = img.height * scaleSize;

                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                canvas.toBlob(
                    (blob) => {
                        // Chuyển Blob thành File
                        const compressedFile = new File([blob], file.name, {
                            type: "image/jpeg",
                            lastModified: Date.now(),
                        });

                        resolve(compressedFile); // Trả về file nén
                    },
                    "image/jpeg",
                    quality
                );
            };

            img.onerror = function (error) {
                reject(error);
            };
        };

        reader.onerror = function (error) {
            reject(error);
        };

        reader.readAsDataURL(file);
    });
}

export async function compressVideo(file, bitrate = "500k") {
    // Khởi tạo FFmpeg từ CDN
    const ffmpeg = window.FFmpeg.createFFmpeg({ log: true });
    const fetchFile = window.FFmpeg.fetchFile; // Lấy fetchFile từ FFmpeg CDN

    await ffmpeg.load();

    const inputName = "input.mp4";
    const outputName = "output.mp4";

    // Ghi file đầu vào vào bộ nhớ ảo của ffmpeg
    ffmpeg.FS("writeFile", inputName, await fetchFile(file));

    // Chạy lệnh ffmpeg để nén video
    await ffmpeg.run(
        "-i", inputName, // File đầu vào
        "-b:v", bitrate, // Giảm bitrate để giảm dung lượng
        "-preset", "fast", // Tối ưu tốc độ xử lý
        outputName // File đầu ra
    );

    // Lấy dữ liệu video đã nén
    const compressedData = ffmpeg.FS("readFile", outputName);
    const compressedBlob = new Blob([compressedData.buffer], { type: "video/mp4" });

    // Tạo File từ Blob
    const compressedFile = new File([compressedBlob], file.name, { type: "video/mp4" });

    return compressedFile; // Trả về File nén
}

export async function compressFile(file) {
    const mimeType = file.type;

    if (mimeType.startsWith("image/")) {
        return await compressImage(file, 0.5); // Nén ảnh với chất lượng 70%
    } else if (mimeType.startsWith("video/")) {
        return await compressVideo(file, "500k"); // Nén video với bitrate 500k
    } else {
        throw new Error("File không phải là ảnh hoặc video");
    }
}