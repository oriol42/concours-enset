/* Formulaire : listes liées, aperçu et allègement des photos, erreurs immédiates. */
(() => {
	"use strict";
	const $ = (s, r = document) => r.querySelector(s);
	const $$ = (s, r = document) => [...r.querySelectorAll(s)];
	const json = (id) => { const e = document.getElementById(id); return e ? JSON.parse(e.textContent) : null; };

	const remplir = (select, valeurs, vide) => {
		const garde = select.value;
		select.replaceChildren(new Option(vide, ""));
		valeurs.forEach(([v, l]) => select.add(new Option(l, v)));
		if (valeurs.some(([v]) => v === garde)) select.value = garde;
	};

	/* Étape 1 : cycle, puis diplôme, puis seulement les options ouvertes à ce diplôme. */
	const offre = json("offre");
	const bloc = $("[data-options]");
	if (offre && bloc) {
		const diplomes = json("diplomes");
		const diplome = $("#c-diplome");
		const cadre = bloc.closest("fieldset");
		const options = () => {
			const liste = offre[diplome.value] || [];
			cadre.hidden = !liste.length;
			const actuel = $('input[name="parcours"]:checked', bloc)?.value || cadre.dataset.actuel;
			bloc.replaceChildren(...liste.map((o) => {
				const v = o.f + "|" + o.o;
				const label = document.createElement("label");
				const input = Object.assign(document.createElement("input"), { type: "radio", name: "parcours", value: v, required: true, checked: v === actuel || liste.length === 1 });
				const span = document.createElement("span");
				const b = document.createElement("b"); b.textContent = o.ol;
				const small = document.createElement("small"); small.textContent = o.fl;
				span.append(b, small); label.append(input, span);
				return label;
			}));
		};
		$$('input[name="cycle"]').forEach((r) => r.addEventListener("change", () => {
			remplir(diplome, Object.entries(diplomes[r.value]), "Choisir un diplôme");
			options();
		}));
		diplome.addEventListener("change", options);
		options();
	}

	/* Étape 3 : les départements de la région choisie. */
	const regions = json("regions");
	if (regions) {
		const region = $("#c-region");
		const dep = $("#c-departement");
		const arr = $("#c-arrondissement");
		const arrondissements = json("arrondissements");
		const majArr = () => remplir(arr, (arrondissements[dep.value] || []).map((a) => [a, a]), dep.value ? "Choisir" : "Choisis le département");
		region.addEventListener("change", () => {
			remplir(dep, (regions[region.value] || []).map((d) => [d, d]), region.value ? "Choisir" : "Choisis la région");
			majArr();
		});
		dep.addEventListener("change", majArr);
	}

	/* Étape 2 : prévenir tout de suite d'un dépassement de la limite d'âge. */
	const age = json("regles-age");
	const zoneAge = $("[data-alerte-age]");
	const naissance = $("#c-date_naissance");
	if (age && zoneAge && naissance && age.cycle) {
		const fonctionnaire = $('input[name="fonctionnaire"]');
		const verifier = () => {
			const m = naissance.value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
			if (!m) { zoneAge.hidden = true; return; }
			const d = new Date(+m[3], m[2] - 1, +m[1]), r = new Date(age.date);
			let a = r.getFullYear() - d.getFullYear();
			if (r.getMonth() < d.getMonth() || (r.getMonth() === d.getMonth() && r.getDate() < d.getDate())) a--;
			const trop = naissance.value && !fonctionnaire.checked && a > age[age.cycle];
			zoneAge.hidden = !trop;
			if (trop) {
				zoneAge.className = "alerte alerte--attention";
				zoneAge.textContent = "Tu auras " + a + " ans à la date de référence, pour une limite de " + age[age.cycle] + " ans. Tu peux continuer : la scolarité examinera ton dossier.";
			}
		};
		naissance.addEventListener("input", verifier);
		fonctionnaire.addEventListener("change", verifier);
		verifier();
	}

	/* Photos : aperçu immédiat et réduction avant l'envoi, utile sur les petites connexions. */
	const alleger = (fichier, cote) => new Promise((resolu) => {
		const url = URL.createObjectURL(fichier);
		const img = new Image();
		img.onload = () => {
			const k = Math.min(1, cote / Math.max(img.width, img.height));
			const c = document.createElement("canvas");
			c.width = Math.round(img.width * k); c.height = Math.round(img.height * k);
			const g = c.getContext("2d");
			g.fillStyle = "#fff"; g.fillRect(0, 0, c.width, c.height);
			g.drawImage(img, 0, 0, c.width, c.height);
			URL.revokeObjectURL(url);
			c.toBlob((b) => resolu(b && b.size < fichier.size ? new File([b], "image.jpg", { type: "image/jpeg" }) : fichier), "image/jpeg", .85);
		};
		img.onerror = () => { URL.revokeObjectURL(url); resolu(fichier); };
		img.src = url;
	});

	$$("[data-depot]").forEach((depot) => {
		const input = $("input[type=file]", depot);
		const apercu = $("[data-apercu]", depot);
		const etat = $("[data-depot-etat]", depot);
		const erreur = $(".champ__erreur", depot);
		input.addEventListener("change", async () => {
			let f = input.files[0];
			if (!f) return;
			erreur.hidden = true;
			depot.classList.remove("champ--invalide");
			if (f.size > 12 * 1024 * 1024) {
				input.value = "";
				erreur.textContent = "Ce fichier est trop lourd. Choisis une photo plus légère.";
				erreur.hidden = false;
				return;
			}
			if (f.type.startsWith("image/")) {
				etat.textContent = "Préparation de la photo…";
				f = await alleger(f, depot.classList.contains("depot--photo") ? 800 : 1600);
				try {
					const dt = new DataTransfer();
					dt.items.add(f);
					input.files = dt.files;
				} catch { /* Navigateur ancien : le fichier d'origine part tel quel. */ }
				const img = new Image();
				img.alt = "";
				img.src = URL.createObjectURL(f);
				apercu.replaceChildren(img);
				etat.textContent = "Photo prête. Touche ici pour la changer.";
			} else {
				etat.textContent = f.name;
			}
			depot.classList.add("est-rempli");
		});
	});

	/* Contrôle avant l'envoi : le serveur revérifie tout. */
	const message = (champ) => champ.closest("[data-champ]");
	const dire = (bloc, texte) => {
		bloc.classList.toggle("champ--invalide", !!texte);
		const e = $(".champ__erreur", bloc);
		if (e) { e.textContent = texte; e.hidden = !texte; }
	};
	window.cebValider = (form) => {
		let premier = null;
		const vus = new Set();
		$$("input[required], select[required]", form).forEach((c) => {
			const bloc = message(c);
			if (!bloc || vus.has(bloc) || c.closest("[hidden]")) return;
			vus.add(bloc);
			const ok = c.type === "radio" ? !!$(`input[name="${c.name}"]:checked`, form) : c.type === "checkbox" ? c.checked : !!c.value.trim();
			dire(bloc, ok ? "" : "Ce champ est obligatoire.");
			if (!ok && !premier) premier = c;
		});
		if (premier) {
			premier.focus({ preventScroll: true });
			message(premier).scrollIntoView({ block: "center" });
		}
		return !premier;
	};
	$$("input, select").forEach((c) => c.addEventListener("input", () => {
		const b = message(c);
		if (b && !b.hasAttribute("data-depot")) dire(b, "");
	}));
})();
