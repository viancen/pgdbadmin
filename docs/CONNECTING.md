# Verbinden met PostgreSQL — Direct & via SSH

PG Admin maakt het mogelijk om te verbinden met **elke** PostgreSQL-database: lokaal, op een server, in de cloud (AWS RDS, DigitalOcean, etc.) of achter een firewall. Net als DataGrip of DBeaver, maar dan in de browser.

Er zijn twee manieren om te verbinden:

1. **Directe verbinding** — als de database bereikbaar is op het netwerk (host + port).
2. **Via SSH-tunnel** — als de database alleen bereikbaar is via een jump host (bastion), met SSH-keys.

---

## 1. Directe verbinding

Gebruik dit wanneer je de hostnaam of het IP-adres en de poort van de PostgreSQL-server kent, en er geen firewall of netwerk tussen zit die directe toegang blokkeert.

### Wat je nodig hebt

| Veld      | Beschrijving |
|----------|----------------|
| **Host** | Hostnaam of IP (bijv. `localhost`, `db.example.com`, `10.0.1.5`) |
| **Port** | Meestal `5432` |
| **User** | PostgreSQL-gebruiker |
| **Password** | Wachtwoord van die gebruiker |
| **SSL**   | Aanvinken als de server SSL vereist (bijv. cloud providers) |

### Voorbeelden

- **Lokale database:** Host `localhost`, Port `5432`, User `postgres`, wachtwoord invullen.
- **Cloud (bijv. DigitalOcean/AWS RDS):** Host = het eindpunt van de provider (bijv. `db-postgresql-ams3-12345.db.ondigitalocean.com`), Port `25060` (of wat de provider geeft), SSL **aan**.
- **Server in eigen netwerk:** Host = IP of hostnaam van de server, Port `5432`, User en wachtwoord van de DB.

### Waar letten

- De **server waar PG Admin draait** moet de database kunnen bereiken. Draait PG Admin op je eigen machine, dan moet jouw machine de host kunnen bereiken. Draait PG Admin op een VPS/Forge, dan moet die VPS uitgaand verkeer naar de DB-host en -poort hebben (firewall/security groups).
- Bij cloud-databases: controleer of je IP (of het IP van de PG Admin-server) is toegestaan in de “Trusted sources” / “Allowed IPs” van de database.

---

## 2. Verbinding via SSH-tunnel (met SSH-keys)

Als de PostgreSQL-server **niet** direct bereikbaar is (bijv. alleen via een jump host of bastion), maak je eerst een **SSH-tunnel**. De tunnel loopt over SSH (met je SSH-key); daarna verbind je in PG Admin naar `localhost` op een lokale poort. Dat is dezelfde aanpak als in DataGrip of DBeaver.

### Stap 1: SSH-key (als je die nog niet hebt)

```bash
ssh-keygen -t ed25519 -C "jouw@email" -f ~/.ssh/pgadmin_jump
```

Geef eventueel een passphrase op. De key staat dan o.a. in `~/.ssh/pgadmin_jump` (privé) en `~/.ssh/pgadmin_jump.pub` (publiek).

Zet de **publieke** key op de jump host (bastion):

```bash
ssh-copy-id -i ~/.ssh/pgadmin_jump.pub user@jump-host.example.com
```

(of handmatig de inhoud van `pgadmin_jump.pub` in `~/.ssh/authorized_keys` op de jump host plaatsen).

### Stap 2: SSH-tunnel starten

Je opent een tunnel: **lokaal** luisteren op een poort en al het verkeer doorsturen naar de echte database via de jump host.

```bash
ssh -i ~/.ssh/pgadmin_jump -L 5433:db-host-intern:5432 user@jump-host.example.com -N
```

Betekenis:

- `-i ~/.ssh/pgadmin_jump` — gebruik deze SSH-key (geen wachtwoord nodig als je geen passphrase hebt, anders vul je die eenmalig in).
- `-L 5433:db-host-intern:5432` — lokaal poort **5433** doorsturen naar **db-host-intern:5432** (zoals de DB vanaf de jump host heet).
- `user@jump-host.example.com` — inloggen op de jump host.
- `-N` — geen shell openen, alleen de tunnel.

**Belangrijk:** `db-host-intern` is de hostnaam of het IP van de PostgreSQL-server **zoals de jump host die ziet** (bijv. een intern IP zoals `10.0.0.5` of een interne hostnaam).

Laat dit terminalvenster open; zolang de tunnel draait, kun je verbinden.

### Stap 3: In PG Admin verbinden

- **Host:** `localhost`
- **Port:** `5433` (de lokale poort uit de `-L` optie)
- **User:** je PostgreSQL-gebruiker
- **Password:** wachtwoord van die gebruiker
- **SSL:** meestal uit (verkeer gaat al via SSH)

Als PG Admin op **dezelfde machine** draait als waar je de tunnel hebt gestart (bijv. jouw laptop), werkt dit direct. Draait PG Admin op een **andere** machine (bijv. een server), dan moet je de tunnel daar starten, of een andere oplossing gebruiken (zie hieronder).

### Meerdere tunnels

Voor een tweede database kun je een andere lokale poort gebruiken:

```bash
ssh -i ~/.ssh/pgadmin_jump -L 5434:andere-db-host:5432 user@jump-host.example.com -N
```

In PG Admin dan Host `localhost`, Port `5434`.

---

## Waar draait PG Admin?

- **PG Admin op je eigen computer:**  
  Start de SSH-tunnel op dezelfde computer. In PG Admin vul je Host `localhost` en de lokale tunnelpoort in.

- **PG Admin op een server (bijv. Laravel Forge):**  
  De server kan niet “jouw” lokale tunnel gebruiken. Opties:
  1. **Tunnel op de server:** SSH naar de server en start daar dezelfde `ssh -L ...` tunnel; dan verbindt PG Admin op die server naar `localhost:5433` (of welke poort je kiest).
  2. **Database bereikbaar maken:** VPN of tijdelijke toegang voor het IP van de PG Admin-server in de firewall van de database.

---

## Samenvatting

| Situatie | Aanpak |
|----------|--------|
| Database direct bereikbaar (host + port) | Directe verbinding: host, port, user, password, eventueel SSL. |
| Database alleen via jump host bereikbaar | SSH-tunnel met key (`ssh -L ...`), daarna in PG Admin: host `localhost`, port = lokale tunnelpoort. |
| PG Admin op server, DB achter jump | Tunnel op de server starten, of DB (tijdelijk) bereikbaar maken voor de server. |

Met directe verbinding en SSH-tunnels kun je in PG Admin verbinden met vrijwel elke PostgreSQL-database, vergelijkbaar met DataGrip of andere desktop-clients, maar dan online.
