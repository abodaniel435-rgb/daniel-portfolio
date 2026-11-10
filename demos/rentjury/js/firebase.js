/* ============================================================
   RENTJURY — firebase.js
   Shared, cross-device storage for community reports using
   Firestore. Replace firebaseConfig below with your own project's
   config (Firebase console → Project settings → Your apps → SDK
   setup and configuration).
============================================================ */

import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import {
  getFirestore, collection, addDoc, onSnapshot,
  query, orderBy, limit, getDocs, serverTimestamp
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

// ---------- 1. YOUR FIREBASE CONFIG ----------
// Get this from: Firebase console → Project settings → General →
// "Your apps" → Web app → SDK setup and configuration.
const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_PROJECT_ID.appspot.com",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const reportsCol = collection(db, "reports");

/**
 * Live-subscribes to the reports collection. callback fires immediately
 * with current data, then again every time any device adds a report.
 * Returns an unsubscribe function.
 */
export function subscribeReports(callback) {
  const q = query(reportsCol, orderBy("createdAt", "desc"), limit(300));
  return onSnapshot(
    q,
    (snapshot) => {
      const reports = snapshot.docs.map(d => ({ id: d.id, ...d.data() }));
      callback(reports, null);
    },
    (error) => {
      console.error("Firestore read failed:", error);
      callback(null, error);
    }
  );
}

/** Writes one report to Firestore. Visible to every device once synced. */
export async function submitReport(report) {
  return addDoc(reportsCol, {
    area: report.area,
    type: report.type,
    amount: Number(report.amount),
    agencyFee: Number(report.agencyFee) || 0,
    legalFee: Number(report.legalFee) || 0,
    cautionFee: Number(report.cautionFee) || 0,
    note: (report.note || "").slice(0, 200),
    date: new Date().toISOString().slice(0, 10),
    createdAt: serverTimestamp()
  });
}

/**
 * Writes the starter/seed reports once, only if the collection is
 * currently empty. Safe to call on every page load — it's a no-op
 * after the first successful seed.
 */
export async function seedIfEmpty(seedData) {
  const snap = await getDocs(query(reportsCol, limit(1)));
  if (!snap.empty) return;
  await Promise.all(seedData.map(r => {
    const { id, ...rest } = r;
    return addDoc(reportsCol, { ...rest, createdAt: serverTimestamp() });
  }));
}
