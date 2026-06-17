import { defineType, defineField } from "sanity";

export default defineType({
  name: "teamMember",
  title: "Mitarbeiter",
  type: "document",
  fields: [
    defineField({
      name: "name",
      title: "Name",
      type: "string",
      validation: (r) => r.required(),
    }),
    defineField({
      name: "role",
      title: "Rolle / Funktion",
      type: "string",
    }),
    defineField({
      name: "photo",
      title: "Foto",
      type: "image",
      options: { hotspot: true },
      fields: [{ name: "alt", title: "Alt-Text", type: "string" }],
    }),
    defineField({
      name: "bio",
      title: "Kurzbeschreibung",
      type: "text",
      rows: 4,
    }),
    defineField({
      name: "email",
      title: "E-Mail",
      type: "string",
    }),
    defineField({
      name: "linkedin",
      title: "LinkedIn-URL",
      type: "url",
    }),
    defineField({
      name: "order",
      title: "Sortierung (kleiner = weiter vorne)",
      type: "number",
    }),
  ],
  orderings: [
    {
      title: "Sortierung",
      name: "orderAsc",
      by: [{ field: "order", direction: "asc" }],
    },
  ],
  preview: {
    select: { title: "name", subtitle: "role", media: "photo" },
  },
});
