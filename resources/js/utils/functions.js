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