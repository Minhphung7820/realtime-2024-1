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