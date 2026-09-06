package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Getter
@Setter
@Entity
@Table(name = "Vocabulary_Senses")
public class VocabularySense {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer vocabularySenseID;

    @ManyToOne(optional = false, fetch = FetchType.LAZY)
    @JoinColumn(name = "vocabularyID")
    private Vocabulary vocabulary;

    @ManyToOne(optional = false, fetch = FetchType.EAGER)
    @JoinColumn(name = "partOfSpeechID")
    private PartOfSpeech partOfSpeech;

    @Column(name = "meaning_vi", nullable = false, columnDefinition = "TEXT")
    private String meaningVi;

    @Column(name = "meaning_en", columnDefinition = "TEXT")
    private String meaningEn;
}
