import { API_BASE_URL } from "../utils/constants.js";

/**
 * Helper function untuk melakukan request API dengan penanganan error dan header yang konsisten.
 * @param {string} fullUrl - URL lengkap untuk request.
 * @param {Object} options - Opsi tambahan untuk request, seperti method dan body.
 * @returns {Promise<Object>} - Respons dari API dalam bentuk objek JSON.
 */
async function makeApiRequest(fullUrl, options = {}) {
  if (!options.headers) options.headers = {};
  options.headers["ngrok-skip-browser-warning"] = "true";
  options.headers.Accept = "application/json";
  if (!options.headers["Content-Type"] && options.method && options.method !== "GET") {
    options.headers["Content-Type"] = "application/json";
  }
  const token = localStorage.getItem("jwt_token");
  if (token) {
    options.headers.Authorization = `Bearer ${token}`;
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

/**
 * Membuat laporan baru untuk seorang siswa.
 * @param {number} studentId
 * @param {string} startDate
 * @param {string} endDate
 * @param {string|null} chartImageBase64
 * @returns {Promise<Object>} Respons dari API.
 */
export async function createReport(studentId, startDate, endDate, chartImageBase64) {
  const reportData = {
    student_id: studentId,
    start_date: startDate,
    end_date: endDate,
    chart_image_base64: chartImageBase64,
  };
  return makeApiRequest(`${API_BASE_URL}/reports/generate`, {
    method: "POST",
    body: JSON.stringify(reportData),
  });
}

/**
 * Mengambil daftar laporan untuk murid yang sedang login.
 * @returns {Promise<Object>} Respons dari API.
 */
export async function listMyReports() {
  return makeApiRequest(`${API_BASE_URL}/reports`, {
    method: "GET",
  });
}

/**
 * Mengunduh file laporan PDF.
 * @param {number} reportId
 */
export async function downloadReport(reportId) {
  // Download PDF, tetap cek error JSON/HTML sebelum proses blob
  if (!reportId) throw new Error("ID laporan tidak valid.");
  const token = localStorage.getItem("jwt_token");
  const headers = {
    "ngrok-skip-browser-warning": "true",
    Authorization: `Bearer ${token}`,
  };
  try {
    const response = await fetch(`${API_BASE_URL}/reports/${reportId}/download`, {
      method: "GET",
      headers,
    });
    const contentType = response.headers.get("content-type");
    if (!response.ok) {
      if (contentType && contentType.indexOf("application/json") !== -1) {
        const errorResult = await response.json();
        throw new Error(errorResult.message || "Gagal mengunduh file laporan.");
      } else {
        const text = await response.text();
        throw new Error(text || "Gagal mengunduh file laporan.");
      }
    }
    if (contentType && contentType.indexOf("application/pdf") !== -1) {
      const filename = `laporan-emosi-${reportId}.pdf`;
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    } else {
      const text = await response.text();
      throw new Error("Response dari server bukan PDF: " + text);
    }
  } catch (error) {
    console.error("Error in downloadReport:", error);
    throw error;
  }
}
