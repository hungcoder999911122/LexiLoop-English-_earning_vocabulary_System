package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Getter
@Setter
@Entity
@Table(name = "Questions")
public class Question {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer questionID;

    @ManyToOne(optional = false)
    @JoinColumn(name = "quizID")
    private Quiz quiz;

    @ManyToOne(optional = false)
    @JoinColumn(name = "vocabularyID")
    private Vocabulary vocabulary;

    @Column(name = "question_text", nullable = false, length = 500)
    private String questionText;

    @Column(name = "question_type")
    private String questionType;
}
