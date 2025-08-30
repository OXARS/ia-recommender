# Manual de Usuario — Buscador de Servicios con IA

## 1) ¿Qué hace el sistema?
Esta aplicación te ayuda a **encontrar proveedores de servicios locales** (seguridad, plomería, electricidad, redes, pintura, etc.).  
Primero busca candidatos por texto (título y descripción). Luego, una **IA de re-ranking (Cohere)** reordena esos candidatos según **relevancia semántica** para tu búsqueda.  
El orden final considera:
- **IA (70%)**: qué tan bien coincide el texto con tu intención.
- **Proximidad (20%)**: favorece proveedores más cercanos a tu ubicación.
- **Rating (10%)**: valoración promedio del proveedor.

> Si la IA no está disponible, el sistema **sigue funcionando** usando solo la coincidencia por texto.

---

## 2) Requisitos
- Navegador moderno (Chrome, Edge, Firefox).
- El sistema debe estar encendido por quien administra (ver Manual del Programador).  
  URL por defecto:
  - Interfaz principal: **http://127.0.0.1:8001/**
  - Demo simple: **http://127.0.0.1:8001/demo.html**

---

## 3) Pantalla principal (elementos)
- **Campo “¿Qué necesitas?”**: Escribe lo que buscas (ej.: *instalar cámaras*, *destapar tubería*, *cerrajero 24h*).
- **Categoría (opcional)**: Restringe resultados (Seguridad, Plomería, Electricidad, Redes, Pintura, etc.).
- **Barrio (opcional)**: Prioriza proveedores de ese sector.
- **Usar mi ubicación (opcional)**: Captura tu ubicación; el sistema calcula cercanía para mejorar el orden.
- **Usar IA de re-ranking**: Activa la IA para un orden más inteligente (recomendado).
- **Top K**: Límite de resultados a mostrar.

---

## 4) Cómo buscar (paso a paso)
1. Escribe tu necesidad en **“¿Qué necesitas?”**  
   *Ejemplo*: `instalar cámaras`.
2. (Opcional) Selecciona **Categoría** y/o **Barrio**.
3. (Opcional) Pulsa **“Usar mi ubicación”** y acepta el permiso del navegador.
4. Deja **“Usar IA de re-ranking”** activado.
5. Presiona **Buscar**.
6. Observa la lista: cada tarjeta muestra **categoría, barrio, título, descripción, proveedor, rating, % IA y Score**.
7. (Opcional) Desactiva **“Usar IA de re-ranking”** y busca de nuevo para **comparar** el orden con y sin IA.

---

## 5) Cómo interpretar los resultados
- **Proveedor** y **Barrio**: quién ofrece y dónde opera.
- **Rating**: promedio (0 a 5).  
- **IA**: porcentaje de relevancia semántica del texto respecto a tu consulta.
- **Score**: puntuación final 0–1 (mezcla IA+proximidad+rating).  
- **“Recomendado”**: aparece cuando el Score supera un umbral de calidad.

---

## 6) Buenas prácticas para encontrar mejor
- Usa **acciones + objeto**: “instalar cámaras”, “cambiar grifería”, “revisar tablero eléctrico”.
- Si no hay resultados, **quita filtros** (categoría/barrio) o prueba **sinónimos**.
- Si puedes, **activa ubicación**: mejora la proximidad.
- Mantén **IA** activada para un orden más inteligente.

---

## 7) Mensajes comunes y qué hacer
- **“Sin coincidencias”**: cambia palabras, quita filtros o reduce “Top K”.
- **IA: OFF**: el sistema sigue mostrando resultados, solo por texto.
- **Error de permisos de ubicación**: busca sin proximidad o activa permisos y repite.

---

## 8) Privacidad
- La ubicación solo se usa para calcular cercanía **durante tu búsqueda**.  
- No se solicita ni almacena información personal.

---

## 9) Ayuda
Si notas datos incorrectos o errores, envía captura de pantalla y la frase que buscaste al responsable técnico.
