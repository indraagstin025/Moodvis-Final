import { API_BASE_URL } from "../utils/constants.js";

/**
 * Mengambil daftar siswa dari API, dengan opsi filter berdasarkan kelas.
 * @param {string|number} classId - ID kelas untuk filter (opsional).
 * @returns {Promise<Array>} Array yang berisi objek-objek siswa.
 */
async function makeApiRequest(fullUrl, options = {}) {
  if (!options.headers) options.headers = {};
  options.headers["ngrok-skip-browser-warning"] = "true";
  options.headers["Accept"] = "application/json";
  if (!options.headers["Content-Type"] && options.method && options.method !== "GET") {
    options.headers["Content-Type"] = "application/json";
  }
  const token = localStorage.getItem("jwt_token");
  if (token) {
    options.headers["Authorization"] = `Bearer ${token}`;
  }
  try {
    const response = await fetch(fullUrl, options);
    const contentType = response.headers.get("content-type");
    if (!response.ok) {
      let errorText = await response.text();
      throw new Error(errorText || `Error: ${response.status} ${response.statusText}`);
    }
    if (contentType && contentType.indexOf("application/json") !== -1) {
      return await response.json();
    } else {
      const textResponse = await response.text();
      console.error("Response bukan JSON:", textResponse);
      throw new Error("Response dari server bukan JSON.");
    }
  } catch (error) {
    console.error(`Kesalahan pada makeApiRequest ke ${fullUrl}:`, error);
    throw error;
  }
}

export async function getMyStudents(classId = "") {
  let apiUrl = `${API_BASE_URL}/teacher/students`;
  if (classId) {
    apiUrl += `?class_id=${classId}`;
  }
  const result = await makeApiRequest(apiUrl, { method: "GET" });
  if (result.status === "success" && Array.isArray(result.students)) {
    return result.students;
  } else {
    throw new Error(result.message || "Format respons dari server tidak valid.");
  }
}
