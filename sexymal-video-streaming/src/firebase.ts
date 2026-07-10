import { initializeApp } from 'firebase/app';
import { getFirestore, initializeFirestore } from 'firebase/firestore';

const firebaseConfig = {
  projectId: "massive-iridium-xn50x",
  appId: "1:661927253230:web:4d99130f3056e5a86b8fff",
  apiKey: "AIzaSyAyoMpBeYzcds6TKy5DNgdfUKlQnlJVtoE",
  authDomain: "massive-iridium-xn50x.firebaseapp.com",
  storageBucket: "massive-iridium-xn50x.firebasestorage.app",
  messagingSenderId: "661927253230"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Initialize Firestore specifying databaseId as per configuration
export const db = initializeFirestore(app, {}, "ai-studio-sexymalvideostre-47532169-6e4d-4033-a1d2-f7965f8820a5");
