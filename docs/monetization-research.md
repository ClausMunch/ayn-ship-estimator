# Monetisering og guideindhold

Undersøgt 17. november 2026. Forslag til prioritering; ingen aftaler, konti eller publicering er foretaget.

## Anbefaling

Udbyg shippingværktøjet til en engelsksproget ressource for AYN Thor-ejere. Værktøjet tiltrækker folk før levering; opsætningsguides kan fastholde dem bagefter. Start med gratis guides, relevant tilbehørs-affiliate og frivillig støtte. Afprøv annoncestrategien ud fra faktiske resultater. Vent med medlemskab og betalte guides, indtil der er dokumenteret efterspørgsel.

Dette er en strategisk vurdering, ikke dokumenteret søgevolumen eller en indtjeningsprognose. Produktionsdata, AdSense-konto, Search Console og affiliate-rapporter er ikke undersøgt.

## Nuværende udgangspunkt i projektet

- `resources/js/Pages/Home.jsx` er et engelsksproget shippingværktøj uden offentligt guideområde.
- `resources/views/app.blade.php` indlæser allerede AdSense. Scriptet dokumenterer hverken kontogodkendelse, annoncevisninger eller indtjening.
- Titel, beskrivelse, canonical og strukturerede data er fælles og handler om forsiden. Nye artikler skal have egne metadata og canonical-adresser.
- `resources/views/sitemap.blade.php` indeholder kun forsiden.
- Der findes intern statistik over sidevisninger og estimater, men `PageView` gemmer ikke besøgt sidesti. Guide- og affiliate-resultater kræver udvidet måling.
- React monteres på klienten. Til artikler anbefales HTML fra serveren, eksempelvis Blade eller Inertia SSR/prerendering, så artikelindhold og metadata er tilgængelige i første svar.

## Indtjeningsmuligheder

| Mulighed | Anvendelse | Prioritet og begrænsning |
| --- | --- | --- |
| Tilbehørs-affiliate | Testede microSD-kort, opladere, kabler, etuier og grips i relevante guides | Først. Publikum har ofte allerede købt selve konsollen. Anbefalinger skal bygge på kompatibilitet og erfaring. |
| Frivillig støtte | Diskret støtte-link ved estimat og efter en nyttig guide | Først. Lav integrationsindsats, men ukendt betalingsvillighed. |
| Displayannoncer | Få placeringer i længere artikler | Mål på eksisterende AdSense, læsbarhed og nettoindtægt før udvidelse. |
| Direkte sponsor | Tydeligt markeret sponsor af guideområdet | Senere, når rækkevidde og målgruppe kan dokumenteres. Ingen sponsor har tilkendegivet interesse. |
| Betalte produkter | Egne testede profiler, personlig hjælp eller vedligeholdte pakkeløsninger | Senere. Support og løbende opdateringer kan gøre det urentabelt. |

Konkrete kandidater:

- [DROIX' vilkår, afsnit 11](https://droix.net/terms-and-conditions/): offentliggjort provision på 2–5 % afhængigt af varen. Godkendelse, relevant sortiment og endelige vilkår skal afklares før integration.
- [Amazon Associates USA](https://affiliate-program.amazon.com/help/node/topic/GRXPHT8U84RAYDXZ): mulig kandidat til tilbehør. Satser afhænger af kategori og marked; amerikanske satser må ikke bruges som et globalt løfte.
- AYN direkte: ingen offentlig tilmeldingsside eller provisionssats bekræftet i undersøgelsen. Mulig henvendelse senere; ingen indtægt budgetteres herfra.
- [Ko-fi](https://help.ko-fi.com/hc/en-us/articles/360002506494-Does-Ko-fi-take-a-fee): Free kan have 0 % platformgebyr på engangsstøtte; Standard tager 5 % og er udgangspunkt for nye konti. Betalingsudbyderens gebyr kommer oveni. Kontroller valgt indstilling.

## Første indhold

| Foreslået URL | Selvstændigt formål |
| --- | --- |
| `/guides` | Guideoversigt med en tydelig indgang fra forsiden. |
| `/guides/wow-forever-ayn-thor` | Hovedguide: modelkrav, valg af launcher, lokal afvikling kontra streaming, installation og første spilstart. |
| `/guides/ds-forever-ayn-thor` | DS Forever: opsætning af to skærme, companion-funktioner, kontrol og opdateringer. |
| `/guides/wow-forever-troubleshooting` | Konkrete problemer med login, lager, opdateringer og hukommelse. Saml fejl her først; del kun op ved behov. |
| `/guides/ayn-thor-first-setup` | Fra udpakning til en brugbar opsætning med testet tilbehør. |
| `/guides/stream-pc-games-ayn-thor` | Senere: afprøvet streamingopsætning. [Moonlight](https://moonlight-stream.org/) er en relevant kandidat. |

Hovedguiden skal hjælpe læseren med at vælge; DS-artiklen skal vise den konkrete opsætning. Undgå to næsten identiske installationsartikler. Andre mulige emner er backup, controllerprofiler, batteri og skærmopsætning; prioriter efter læsernes spørgsmål og målte søgninger.

## Hvad researchen bekræfter om WoW og DS Forever

[DS Forever](https://github.com/Jreese702/DS-Forever) er et uofficielt Android-projekt, afledt af WoW Forever og GameNative, med Thor-specifik companion og to skærme. README beskriver både lokal installation og PC-streaming, men også begrænset testdækning og forbehold om den offentlige opdateringskanal. En guide skal derfor kontrollere den aktuelle release og testes på en fysisk Thor før publicering. Brugeren skal selv have autoriseret spilinstallation/konto.

[WoW Forever for Android](https://github.com/jaredgei/wow-forever-android) beskriver understøttelse af Snapdragon 8-serien, cirka 60 GB til spildata og ekstra intern plads til runtime. Dette er upstream-oplysninger, ikke egne målinger. [Issue #3](https://github.com/jaredgei/wow-forever-android/issues/3) dokumenterer rapporter om hukommelsesrelaterede crashes på Thor Base med 8 GB RAM. Lov derfor ikke samme oplevelse på alle varianter eller bestemte FPS uden egne målinger.

For hver guide: angiv testdato, model/RAM, firmware, appversion, downloadkilde og kendte begrænsninger. Brug egne screenshots og link til projektets release. Undlad at spejle spilfiler eller APK'er som en del af første løsning. Der er ikke foretaget en vurdering af Blizzards accept af disse launchere; en guide må ikke love officiel support eller risikofri kontobrug.

## Økonomi: følsomhed frem for prognose

Nedenstående tal er selvvalgte regneforudsætninger, ikke branchetal. Sidevisninger er ikke det samme som unikke købere.

Ved 10.000 relevante guidesidevisninger pr. måned:

- Affiliate: 10.000 × 5 % udgående klik × 3 % køb × 400 kr. ordreværdi × 3 % provision = **180 kr./måned**, før eventuelle returneringer og afviste køb.
- Annoncer: 10.000 / 1.000 × antaget side-RPM på 10–40 kr. = **100–400 kr./måned**.
- Frivillig støtte: antaget 10 bidrag × 30 kr. = **300 kr./måned** før gebyrer.

Tallene kan ende lavere eller på nul. De er ikke tilsammen en forventning. Et mål på 5.000 kr./måned alene fra affiliate kræver med samme forudsætninger cirka 556 godkendte køb eller 278.000 guidesidevisninger. Indhold, test og vedligehold skal holdes op mod indtægten.

## Praktiske krav ved gennemførelse

- Mærk affiliate-links og sponsorer tydeligt som reklame; se [Forbrugerombudsmandens sag om kommissionslinks](https://forbrugerombudsmanden.dk/find-sager/sager/markedsfoeringsloven/sager-efter-markedsfoeringsloven/oevrige/2405252-mediehus-overtraadte-forbuddet-mod-skjulte-kommercielle-hensigter-og-vildledning).
- Kontroller AdSense Privacy & messaging og samtykkeopsætning. Ingen CMP-komponent er fundet i den læste frontend, men opsætning via Google-kontoen er ikke verificeret. Google beskriver krav om certificeret CMP til personlige annoncer i EØS, UK og Schweiz i [sin vejledning](https://support.google.com/adsense/answer/13554116?hl=en).
- Brug særskilt tilmelding til et kommercielt guidenyhedsbrev; eksisterende shippingtilmelding er ikke grundlag for at antage markedsføringssamtykke. Se [vejledning om spam](https://forbrug.dk/emner/reklamer-og-spam/spam).

## Foreslået gennemførelse og måling

1. Registrer baseline: seneste 30 dages trafik, besøgslande, trafikkilder og faktisk AdSense-side-RPM. Suppler intern statistik med Search Console og måling pr. artikel.
2. Byg guideoversigt, artikelskabelon, individuelle metadata, sitemap og navigation. Start med WoW-hovedguide og DS Forever efter fysisk test.
3. Tilføj frivillig støtte og få relevante affiliate-links efter godkendelse. Mål klik pr. side og produkt; brug affiliate-rapporten til godkendte køb og provision.
4. Følg nye sider efter 30, 60 og 90 dage: søgevisninger, klik, læsere, affiliate-indtægt, støtte og annonceindtægt. SEO kan kræve længere tid; perioderne er kontrolpunkter.
5. Udbyg de emner, der giver trafik eller løser gentagne spørgsmål. Vurder sponsorater, når resultaterne kan præsenteres troværdigt.

Næste konkrete leverance er guideområdet og to fysisk afprøvede artikler. Før det kan økonomien kvalificeres med produktionens trafik- og AdSense-tal.
