/* Partagé par toutes les pages : copie du numéro de compte, envoi unique, onglets. */
(() => {
	"use strict";
	const $$ = (s, r = document) => [...r.querySelectorAll(s)];

	$$("[data-copier]").forEach((b) => b.addEventListener("click", async () => {
		const etat = b.querySelector("[data-copie-etat]");
		try {
			await navigator.clipboard.writeText(b.dataset.copier);
			etat.textContent = "Copié";
			setTimeout(() => { etat.textContent = "Copier"; }, 2000);
		} catch {
			etat.textContent = "Sélectionne le numéro";
		}
	}));

	$$('[role="tablist"]').forEach((liste) => {
		const onglets = $$('[role="tab"]', liste);
		onglets.forEach((o, i) => {
			const activer = () => onglets.forEach((x) => {
				const actif = x === o;
				x.setAttribute("aria-selected", actif);
				document.getElementById(x.getAttribute("aria-controls")).hidden = !actif;
			});
			o.addEventListener("click", activer);
			o.addEventListener("keydown", (e) => {
				if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") return;
				const s = onglets[(i + (e.key === "ArrowRight" ? 1 : onglets.length - 1)) % onglets.length];
				s.focus(); s.click();
			});
		});
	});

	/* Trois messages d'erreur plutôt qu'un seul à la fois, et jamais deux envois. */
	$$("[data-etape-form]").forEach((form) => {
		form.addEventListener("submit", (e) => {
			if (window.cebValider && !window.cebValider(form)) { e.preventDefault(); return; }
			const b = form.querySelector("[data-envoi]");
			if (b) {
				b.disabled = true;
				const t = b.querySelector("span");
				if (t) t.textContent = "Enregistrement…";
			}
		});
	});
	/* Retour arrière du navigateur : le bouton doit redevenir actif. */
	addEventListener("pageshow", () => $$("[data-envoi]").forEach((b) => { b.disabled = false; }));

	document.querySelector("[data-resume]")?.focus();
	document.querySelector("[data-ouvrir]")?.focus();
})();
