package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Getter
@Setter
@Entity
@Table(name = "Vocabulary_Examples")
public class VocabularyExample {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer exampleID;

    @ManyToOne(optional = false, fetch = FetchType.LAZY)
    @JoinColumn(name = "vocabularySenseID")
    private VocabularySense sense;

    @Column(name = "sentence_en", nullable = false, columnDefinition = "TEXT")
    private String sentenceEn;

    @Column(name = "sentence_vi", columnDefinition = "TEXT")
    private String sentenceVi;
}
