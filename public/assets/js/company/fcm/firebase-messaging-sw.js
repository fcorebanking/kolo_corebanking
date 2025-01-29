importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyDxxIqopwqL3xR8bR6436K9DcbJ9jyHv-k",
    projectId: "financeapp-9156c",
    messagingSenderId: "1026079808715",
    appId: "1:1026079808715:web:a94f354baadf17a16e8f4e",
});

const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function({data:{title,body,icon}}) {
    return self.registration.showNotification(title,{body,icon});
});
