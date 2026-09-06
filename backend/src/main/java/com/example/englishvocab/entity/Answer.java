package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Getter
@Setter
@Entity
@Table(name = "Answers")
public class Answer {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer answerID;

    @ManyToOne(optional = false)
    @JoinColumn(name = "questionID")
    private Question question;

    @Column(name = "answer_text", nullable = false, length = 500)
    private String answerText;

    @Column(name = "is_correct")
    private Boolean correct;
}
