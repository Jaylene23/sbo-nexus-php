<?php
// Shared green look for the login and registration pages.
?>
<style>

/* =========================================================
   SBO NEXUS LOGIN
   FORCE GREEN GLASS BACKGROUND
   ========================================================= */

/*
   IMPORTANT:
   The previous blue background was coming from the
   existing .auth-wrap styling in includes/style.php.

   Therefore we override BOTH body AND .auth-wrap.
*/


html,
body {
    min-height: 100%;
    margin: 0;
}


/* =========================================================
   BODY
   ========================================================= */

body {
    font-family: 'Inter', sans-serif !important;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(74, 222, 128, 0.35) 0%,
            rgba(74, 222, 128, 0.12) 25%,
            transparent 45%
        ),

        radial-gradient(
            circle at 90% 90%,
            rgba(52, 211, 153, 0.35) 0%,
            rgba(52, 211, 153, 0.12) 25%,
            transparent 45%
        ),

        linear-gradient(
            135deg,
            #022c22 0%,
            #064e3b 30%,
            #047857 65%,
            #14532d 100%
        ) !important;

    background-color: #064e3b !important;

    background-attachment: fixed !important;

    color: #ffffff;
}


/* =========================================================
   AUTH WRAP
   THIS IS THE IMPORTANT FIX
   ========================================================= */

.auth-wrap {

    position: relative !important;

    min-height: 100vh !important;
    min-height: 100dvh !important;

    width: 100% !important;

    /*
       FORCE THE AREA OUTSIDE THE CARD TO GREEN
    */

    background:
        radial-gradient(
            circle at 10% 15%,
            rgba(74, 222, 128, 0.32) 0%,
            transparent 35%
        ),

        radial-gradient(
            circle at 90% 85%,
            rgba(52, 211, 153, 0.30) 0%,
            transparent 38%
        ),

        radial-gradient(
            circle at 50% 50%,
            rgba(34, 197, 94, 0.12) 0%,
            transparent 55%
        ),

        linear-gradient(
            135deg,
            #022c22 0%,
            #064e3b 32%,
            #047857 65%,
            #14532d 100%
        ) !important;

    background-color: #064e3b !important;

    background-attachment: fixed !important;

    /*
       Remove any old blue background image
    */

    background-image:
        radial-gradient(
            circle at 10% 15%,
            rgba(74, 222, 128, 0.32) 0%,
            transparent 35%
        ),
        radial-gradient(
            circle at 90% 85%,
            rgba(52, 211, 153, 0.30) 0%,
            transparent 38%
        ),
        linear-gradient(
            135deg,
            #022c22 0%,
            #064e3b 32%,
            #047857 65%,
            #14532d 100%
        ) !important;

    z-index: 0;
}


/* =========================================================
   GREEN GLASS LIGHT — LEFT
   ========================================================= */

.auth-wrap::before {

    content: "";

    position: fixed;

    width: 500px;
    height: 500px;

    left: -220px;
    top: -200px;

    border-radius: 50%;

    background:
        rgba(74, 222, 128, 0.20);

    filter: blur(80px);

    pointer-events: none;

    z-index: -1;
}


/* =========================================================
   GREEN GLASS LIGHT — RIGHT
   ========================================================= */

.auth-wrap::after {

    content: "";

    position: fixed;

    width: 500px;
    height: 500px;

    right: -220px;
    bottom: -220px;

    border-radius: 50%;

    background:
        rgba(52, 211, 153, 0.22);

    filter: blur(90px);

    pointer-events: none;

    z-index: -1;
}


/* =========================================================
   AUTH CONTENT
   ========================================================= */

.auth-heading,
.auth-card {

    position: relative;

    z-index: 2;
}


/* =========================================================
   HEADER TEXT
   ========================================================= */

.auth-eyebrow {

    color:
        rgba(236, 253, 245, 0.92) !important;

}

.auth-title {

    color:
        #ffffff !important;

}


/* =========================================================
   SBO LOGO
   ========================================================= */

.auth-logo {

    object-fit: contain;

    filter:
        drop-shadow(
            0 8px 18px rgba(0, 0, 0, 0.28)
        );

}


/* =========================================================
   KEEP THE LOGIN CARD
   ========================================================= */

/*
   We intentionally DO NOT replace the card's existing
   design. The green glass effect is for the area OUTSIDE
   the card.
*/


/* =========================================================
   FORM LABELS
   ========================================================= */

.auth-card .field label {

    color:
        #ffffff !important;

}


/* =========================================================
   INPUTS
   ========================================================= */

.auth-card .field input {

    color:
        #111827 !important;

}


/* =========================================================
   GOOGLE BUTTON (official Google button styling — white,
   not the green ICS theme used for the password Login button)
   ========================================================= */

.auth-card .btn-google {

    background:
        #ffffff !important;

    color:
        #3c4043 !important;

    border:
        1px solid #dadce0 !important;

    box-shadow:
        0 1px 2px rgba(60, 64, 67, 0.15) !important;

}


.auth-card .btn-google:hover {

    background:
        #f8f9fa !important;

    box-shadow:
        0 1px 3px rgba(60, 64, 67, 0.25) !important;

}


/* =========================================================
   LOGIN BUTTON
   ========================================================= */

.auth-card .btn-primary {

    background:
        linear-gradient(
            135deg,
            #16a34a 0%,
            #15803d 100%
        ) !important;

    color:
        #ffffff !important;

    border:
        1px solid rgba(187, 247, 208, 0.25) !important;

    box-shadow:
        0 8px 22px rgba(21, 128, 61, 0.30) !important;

}


.auth-card .btn-primary:hover {

    background:
        linear-gradient(
            135deg,
            #22c55e 0%,
            #16a34a 100%
        ) !important;

    box-shadow:
        0 12px 28px rgba(21, 128, 61, 0.38) !important;

}


/* =========================================================
   REGISTER LINK
   ========================================================= */

.auth-foot {

    color:
        rgba(236, 253, 245, 0.90) !important;

}


.auth-foot a {

    color:
        #bbf7d0 !important;

}


.auth-foot a:hover {

    color:
        #ffffff !important;

}


/* =========================================================
   GOOGLE INFORMATION
   ========================================================= */

.auth-card strong {

    color:
        #bbf7d0;

}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 600px) {

    .auth-wrap {

        min-height: 100dvh !important;

        background-attachment: scroll !important;

    }

}



/* Registration page: dropdowns and read-only fields match the text inputs */
.auth-card .field select,
.auth-card .field input:disabled {
    color: #111827 !important;
}
.auth-card .field input:disabled {
    opacity: .85;
    -webkit-text-fill-color: #111827;
}
</style>
